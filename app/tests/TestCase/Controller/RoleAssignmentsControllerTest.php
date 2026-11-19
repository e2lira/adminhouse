<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SecondFactorTestTrait;
use App\Test\Support\SignsInTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The role-assignment screens (RB-R3): authorization matrix, grant, end, CSRF.
 */
class RoleAssignmentsControllerTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SecondFactorTestTrait;
    use SignsInTrait;

    private int $devA;
    private int $devB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->devA = $this->createDevelopment('Residencial Norte');
        $this->devB = $this->createDevelopment('Residencial Sur');
        Configure::write('debug', false);
    }

    private function indexUrl(int $development): string
    {
        return '/developments/' . $development . '/role-assignments';
    }

    private function endUrl(int $development, int $assignment): string
    {
        return $this->indexUrl($development) . '/' . $assignment . '/end';
    }

    private function signInAdminOfA(): int
    {
        $userId = $this->signInAs('ann');
        $this->assignRole($userId, 'administrator', $this->devA);

        return $userId;
    }

    private function assignmentId(int $userId, string $role): int
    {
        return (int)$this->db()->execute(
            'SELECT ra.id FROM role_assignments ra JOIN roles r ON r.id = ra.role_id
             WHERE ra.user_id = ? AND r.code = ?',
            [$userId, $role],
        )->fetch()[0];
    }

    private function countAssignments(): int
    {
        return (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0];
    }

    // --- authorization matrix -------------------------------------------------------------------------------

    public function testAnonymousVisitorIsSentToLoginOnEveryRoute(): void
    {
        $this->enableCsrfToken();
        $this->get($this->indexUrl($this->devA));
        $this->assertRedirectContains('/login');

        $this->post($this->indexUrl($this->devA), ['username' => 'x']);
        $this->assertRedirectContains('/login');

        $this->post($this->endUrl($this->devA, 1));
        $this->assertRedirectContains('/login');
    }

    public function testPendingTotpSessionCannotReachTheScreens(): void
    {
        $this->enableCsrfToken();
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $this->post('/login', ['username' => 'root', 'password' => self::PASSWORD]);
        $this->carrySession();

        $this->get($this->indexUrl($this->devA));
        $this->assertRedirectContains('/login');

        $this->post($this->indexUrl($this->devA), ['username' => 'ann']);
        $this->assertRedirectContains('/login');
        $this->assertSame(1, $this->countAssignments());
    }

    public function testSuperAdministratorSeesTheListOfAnyDevelopment(): void
    {
        $userId = $this->signInAs('root');
        $this->assignRole($userId, 'super_administrator', null);
        $guard = $this->createUser('gary');
        $this->assignRole($guard, 'guard', $this->devB);

        $this->get($this->indexUrl($this->devB));

        $this->assertResponseOk();
        $this->assertResponseContains('Residencial Sur');
        $this->assertResponseContains('gary');
    }

    public function testAdministratorSeesTheirDevelopmentRows(): void
    {
        $this->signInAdminOfA();
        $accountant = $this->createUser('carl');
        $this->assignRole($accountant, 'accountant', $this->devA);
        $this->assignRole($accountant, 'resident', $this->devB);

        $this->get($this->indexUrl($this->devA));

        $this->assertResponseOk();
        $this->assertResponseContains('carl');
        $this->assertResponseContains('Vigente');
        $this->assertResponseContains('Contador');
    }

    public function testAdministratorOfAnotherDevelopmentGetsNotFoundIndistinguishableFromMissing(): void
    {
        $this->signInAdminOfA();

        $this->get($this->indexUrl($this->devB));
        $other = $this->_response->getStatusCode();
        $otherBody = (string)$this->_response->getBody();

        $this->get($this->indexUrl(999999));

        $this->assertSame(404, $other);
        $this->assertResponseCode(404);
        $this->assertStringNotContainsString('Residencial Sur', $otherBody);
        $normalize = static fn(string $body): string => (string)preg_replace('~/developments/\d+/~', '/developments/N/', $body);
        $this->assertSame($normalize($otherBody), $normalize((string)$this->_response->getBody()));
    }

    public function testSuperAdministratorGetsNotFoundForADevelopmentThatDoesNotExist(): void
    {
        $root = $this->signInAs('root');
        $this->assignRole($root, 'super_administrator', null);

        $this->get($this->indexUrl($this->devB));
        $this->assertResponseOk();

        $this->get($this->indexUrl(999999));
        $this->assertResponseCode(404);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rolesWithoutTheManagePermission(): array
    {
        return ['accountant' => ['accountant'], 'guard' => ['guard'], 'resident' => ['resident']];
    }

    #[DataProvider('rolesWithoutTheManagePermission')]
    public function testRolesWithoutTheManagePermissionGetNotFound(string $role): void
    {
        $userId = $this->signInAs('actor');
        $this->assignRole($userId, $role, $this->devA);

        $this->get($this->indexUrl($this->devA));

        $this->assertResponseCode(404);
    }

    public function testUserWithoutAnyAssignmentGetsNotFound(): void
    {
        $this->signInAs('nobody');

        $this->get($this->indexUrl($this->devA));

        $this->assertResponseCode(404);
    }

    public function testNonNumericDevelopmentIsNotRouted(): void
    {
        $this->signInAdminOfA();

        $this->get('/developments/abc/role-assignments');

        $this->assertResponseCode(404);
    }

    public function testOnlyDeclaredMethodsAreRouted(): void
    {
        $this->signInAdminOfA();
        $this->enableCsrfToken();

        $this->put($this->indexUrl($this->devA), []);
        $this->assertResponseCode(404);

        $this->get($this->endUrl($this->devA, 1));
        $this->assertResponseCode(404);
    }

    // --- grant ----------------------------------------------------------------------------------------------

    private function grantData(array $overrides = []): array
    {
        return $overrides + [
            'username' => 'bob',
            'role_id' => (string)$this->roleId('accountant'),
            'starts_on' => '',
            'ends_on' => '',
            'read_only' => '0',
        ];
    }

    public function testFormOffersOnlyGrantableRoles(): void
    {
        $this->signInAdminOfA();

        $this->get($this->indexUrl($this->devA));

        $this->assertResponseOk();
        $this->assertResponseContains('Contador');
        $this->assertResponseContains('Guardia');
        $this->assertResponseNotContains('Superadministrador');
        $this->assertResponseNotRegExp('/<option[^>]*>Administrador</');
        $this->assertResponseContains('name="username"');
    }

    public function testGrantHappyPathRedirectsAndShowsTheRow(): void
    {
        $this->signInAdminOfA();
        $bob = $this->createUser('bob');
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData());

        $this->assertRedirect($this->indexUrl($this->devA));
        $this->assertSame(2, $this->countAssignments());
        $this->get($this->indexUrl($this->devA));
        $this->assertResponseContains('bob');
        $this->assertSame('granted', $this->db()->execute('SELECT action FROM role_assignment_events')->fetch()[0]);
        $this->assertNotSame(0, $bob);
    }

    public function testGrantInAnotherDevelopmentIsNotFoundAndWritesNothing(): void
    {
        $this->signInAdminOfA();
        $this->createUser('bob');
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devB), $this->grantData());

        $this->assertResponseCode(404);
        $this->assertSame(1, $this->countAssignments());
    }

    public function testGrantingSuperAdministratorIsRejectedWithAFriendlyMessage(): void
    {
        $this->signInAdminOfA();
        $this->createUser('bob');
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData(['role_id' => (string)$this->roleId('super_administrator')]));

        $this->assertResponseOk();
        $this->assertResponseContains('No se puede otorgar ese rol');
        $this->assertSame(1, $this->countAssignments());
    }

    public function testAdministratorCannotGrantAdministrator(): void
    {
        $this->signInAdminOfA();
        $this->createUser('bob');
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData(['role_id' => (string)$this->roleId('administrator')]));

        $this->assertResponseOk();
        $this->assertResponseContains('Solo un superadministrador');
        $this->assertSame(1, $this->countAssignments());
    }

    public function testSuperAdministratorCanGrantAdministrator(): void
    {
        $root = $this->signInAs('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->createUser('bob');
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData(['role_id' => (string)$this->roleId('administrator')]));

        $this->assertRedirect($this->indexUrl($this->devA));
        $this->assertSame(
            1,
            (int)$this->db()->execute(
                'SELECT COUNT(*) FROM role_assignments WHERE user_id = (SELECT id FROM users WHERE username = ?)',
                ['bob'],
            )->fetch()[0],
        );
    }

    public function testSelfGrantIsRejected(): void
    {
        $this->signInAdminOfA();
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData(['username' => 'ann']));

        $this->assertResponseOk();
        $this->assertResponseContains('No puedes cambiar tus propios roles');
        $this->assertSame(1, $this->countAssignments());
    }

    public function testInvalidPeriodAndUnknownUserAreRejectedAndTheFormKeepsTheInput(): void
    {
        $this->signInAdminOfA();
        $this->createUser('bob');
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData(['starts_on' => '2026-12-10', 'ends_on' => '2026-12-09']));
        $this->assertResponseOk();
        $this->assertResponseContains('fecha de término no puede ser anterior');
        $this->assertResponseContains('value="bob"');

        $this->post($this->indexUrl($this->devA), $this->grantData(['username' => 'ghost']));
        $this->assertResponseOk();
        $this->assertResponseContains('No existe un usuario activo');
        $this->assertSame(1, $this->countAssignments());
    }

    public function testDuplicateGrantShowsAFriendlyMessage(): void
    {
        $this->signInAdminOfA();
        $bob = $this->createUser('bob');
        $this->assignRole($bob, 'accountant', $this->devA);
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData());

        $this->assertResponseOk();
        $this->assertResponseContains('ya tiene ese rol');
        $this->assertSame(2, $this->countAssignments());
    }

    public function testMessagesAreEscaped(): void
    {
        $this->signInAdminOfA();
        $this->enableCsrfToken();

        $this->post($this->indexUrl($this->devA), $this->grantData(['username' => '<script>x</script>']));

        $this->assertResponseOk();
        $this->assertResponseNotContains('<script>x</script>');
    }

    public function testGrantRequiresACsrfToken(): void
    {
        $this->signInAdminOfA();
        $this->createUser('bob');
        $this->_csrfToken = false;
        $this->_cookie = [];

        $this->post($this->indexUrl($this->devA), $this->grantData());

        $this->assertResponseCode(403);
        $this->assertSame(1, $this->countAssignments());
    }

    // --- end ------------------------------------------------------------------------------------------------

    public function testEndKeepsTheRowAndShowsItAsEnded(): void
    {
        $this->signInAdminOfA();
        $bob = $this->createUser('bob');
        $this->assignRole($bob, 'accountant', $this->devA);
        $id = $this->assignmentId($bob, 'accountant');
        $this->enableCsrfToken();

        $this->post($this->endUrl($this->devA, $id));

        $this->assertRedirect($this->indexUrl($this->devA));
        $this->assertSame(2, $this->countAssignments());
        $this->assertNotNull($this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$id])->fetch()[0]);
        $this->get($this->indexUrl($this->devA));
        $this->assertResponseContains('Terminada');
        $this->assertSame('ended', $this->db()->execute('SELECT action FROM role_assignment_events')->fetch()[0]);
    }

    public function testEndOfAnotherDevelopmentsAssignmentIsNotFound(): void
    {
        $this->signInAdminOfA();
        $bob = $this->createUser('bob');
        $this->assignRole($bob, 'accountant', $this->devB);
        $id = $this->assignmentId($bob, 'accountant');
        $this->enableCsrfToken();

        $this->post($this->endUrl($this->devB, $id));
        $this->assertResponseCode(404);

        $this->post($this->endUrl($this->devA, $id));
        $this->assertResponseCode(404);
        $this->assertNull($this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$id])->fetch()[0]);
    }

    public function testEndingYourOwnAssignmentIsRejected(): void
    {
        $adminId = $this->signInAdminOfA();
        $own = $this->assignmentId($adminId, 'administrator');
        $this->enableCsrfToken();

        $this->post($this->endUrl($this->devA, $own));

        $this->assertRedirect($this->indexUrl($this->devA));
        $this->assertNull($this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$own])->fetch()[0]);
        $this->carrySession();
        $this->get($this->indexUrl($this->devA));
        $this->assertResponseContains('No puedes cambiar tus propios roles');
    }

    public function testEndRequiresACsrfToken(): void
    {
        $this->signInAdminOfA();
        $bob = $this->createUser('bob');
        $this->assignRole($bob, 'accountant', $this->devA);
        $id = $this->assignmentId($bob, 'accountant');
        $this->_csrfToken = false;
        $this->_cookie = [];

        $this->post($this->endUrl($this->devA, $id));

        $this->assertResponseCode(403);
        $this->assertNull($this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$id])->fetch()[0]);
    }

    public function testEndedAssignmentsAreNotOfferedTheEndButton(): void
    {
        $this->signInAdminOfA();
        $bob = $this->createUser('bob');
        $this->assignRole($bob, 'accountant', $this->devA, -30, -2);
        $id = $this->assignmentId($bob, 'accountant');

        $this->get($this->indexUrl($this->devA));

        $this->assertResponseOk();
        $this->assertResponseNotContains($this->endUrl($this->devA, $id));
    }

    // --- navigation -----------------------------------------------------------------------------------------

    public function testDevelopmentsListLinksOnlyToManageableDevelopments(): void
    {
        $this->signInAdminOfA();
        $accountant = $this->createUser('carl');
        $this->assignRole($accountant, 'accountant', $this->devB);
        $userId = (int)$this->db()->execute('SELECT id FROM users WHERE username = ?', ['ann'])->fetch()[0];
        $this->assignRole($userId, 'supervisor', $this->devB);

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseContains($this->indexUrl($this->devA));
        $this->assertResponseNotContains($this->indexUrl($this->devB));
    }
}
