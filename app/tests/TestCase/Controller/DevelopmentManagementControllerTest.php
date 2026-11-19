<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Exception\DevelopmentScopeViolation;
use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SignsInTrait;
use Cake\Core\Configure;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The development management screens (add, edit, archive, unarchive): super-administrator only.
 */
class DevelopmentManagementControllerTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SignsInTrait;

    private const BANK_ACCOUNT = '012180001234567897';

    private int $devA;
    private int $devB;
    private ?Closure $saveListener = null;
    private mixed $debugBefore = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->devA = $this->createDevelopment('Residencial Norte');
        $this->devB = $this->createDevelopment('Residencial Sur');
        $this->db()->execute(
            'UPDATE developments SET bank_name = ?, bank_account = ? WHERE id = ?',
            ['Banco Secreto', self::BANK_ACCOUNT, $this->devB],
        );
        $this->debugBefore = Configure::read('debug');
        Configure::write('debug', false);
    }

    protected function tearDown(): void
    {
        if ($this->saveListener !== null) {
            EventManager::instance()->off('Model.beforeSave', $this->saveListener);
        }
        Configure::write('debug', $this->debugBefore);
        parent::tearDown();
    }

    private function signInSuper(): int
    {
        $userId = $this->signInAs('root');
        $this->assignRole($userId, 'super_administrator', null);

        return $userId;
    }

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'subdivision',
            'name' => 'Lomas del Valle',
            'section_label' => 'Calle',
            'number_label' => 'Número',
            'timezone' => 'America/Mexico_City',
            'address' => 'Av. Central 100',
            'phone' => '5555550100',
            'rfc' => 'LVA010101AB1',
            'bank_name' => 'Banco Uno',
            'bank_account' => '014180000000000001',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function row(string $name): ?array
    {
        $row = $this->db()->execute('SELECT * FROM developments WHERE name = ?', [$name])->fetch('assoc');

        return $row === false ? null : $row;
    }

    private function countDevelopments(): int
    {
        return (int)$this->db()->execute('SELECT COUNT(*) FROM developments')->fetch()[0];
    }

    /**
     * Who may not manage developments, as sign-in recipes: [role, development key, read-only].
     *
     * @return array<string, array{string, bool}>
     */
    public static function refusedActors(): array
    {
        return [
            'administrator' => ['administrator', false],
            'data_entry' => ['data_entry', false],
            'read-only administrator' => ['administrator', true],
            'accountant' => ['accountant', false],
        ];
    }

    private function signInWithRole(string $role, bool $readOnly): void
    {
        $userId = $this->signInAs('ann');
        $this->assignRole($userId, $role, $this->devA, readOnly: $readOnly);
    }

    // --- add --------------------------------------------------------------------------------------------------

    public function testAddFormDefaultsTheTimezoneToMexicoCity(): void
    {
        $this->signInSuper();

        $this->get('/developments/add');

        $this->assertResponseOk();
        $this->assertResponseContains('value="America/Mexico_City"');
        $this->assertResponseContains('name="bank_account"');
    }

    public function testSuperCreatesADevelopmentWithEveryField(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/add', $this->payload());

        $this->assertRedirect('/developments');
        $this->assertFlashMessage('Desarrollo creado.');
        $row = $this->row('Lomas del Valle');
        $this->assertNotNull($row);
        $this->assertSame('subdivision', $row['type']);
        $this->assertSame('Av. Central 100', $row['address']);
        $this->assertSame('014180000000000001', $row['bank_account']);
        $this->assertNull($row['archived_at']);
    }

    public function testSuperCreatesADevelopmentWithoutTheOptionalFields(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/add', $this->payload([
            'address' => '', 'phone' => '', 'rfc' => '', 'bank_name' => '', 'bank_account' => '',
        ]));

        $this->assertRedirect('/developments');
        $row = $this->row('Lomas del Valle');
        $this->assertNotNull($row);
        foreach (['address', 'phone', 'rfc', 'bank_name', 'bank_account'] as $field) {
            $this->assertNull($row[$field], $field);
        }
    }

    public function testACreatedDevelopmentIsVisibleToTheSuperAdministrator(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->post('/developments/add', $this->payload());

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseContains('Lomas del Valle');
    }

    public function testAnInvalidDevelopmentIsShownAgainWithErrorsAndNothingIsSaved(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $before = $this->countDevelopments();

        $this->post('/developments/add', $this->payload(['name' => '  ', 'timezone' => 'Mars/Olympus']));

        $this->assertResponseOk();
        $this->assertResponseContains('error-message');
        $this->assertResponseContains('value="Mars/Olympus"');
        $this->assertResponseNotContains('Desarrollo creado.');
        $this->assertSame($before, $this->countDevelopments());
    }

    public function testAddIgnoresFieldsThatAreNotEditable(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/add', $this->payload(['id' => '999', 'archived_at' => '2020-01-01 00:00:00']));

        $row = $this->row('Lomas del Valle');
        $this->assertNotNull($row);
        $this->assertNotSame(999, (int)$row['id']);
        $this->assertNull($row['archived_at']);
    }

    #[DataProvider('refusedActors')]
    public function testNobodyButTheSuperAdministratorMayOpenOrSubmitTheAddForm(string $role, bool $readOnly): void
    {
        $this->signInWithRole($role, $readOnly);
        $this->enableCsrfToken();
        $before = $this->countDevelopments();

        $this->get('/developments/add');
        $this->assertResponseCode(403);

        $this->post('/developments/add', $this->payload());
        $this->assertResponseCode(403);
        $this->assertNull($this->row('Lomas del Valle'));
        $this->assertSame($before, $this->countDevelopments());
    }

    public function testAddRequiresACsrfToken(): void
    {
        $this->signInSuper();
        $this->_csrfToken = false;
        $this->_cookie = [];

        $this->post('/developments/add', $this->payload());

        $this->assertResponseCode(403);
        $this->assertNull($this->row('Lomas del Valle'));
    }

    public function testAFailedSaveOnAddIsAnErrorNotASuccess(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->useSaveThat(fn() => false);
        $before = $this->countDevelopments();

        $this->post('/developments/add', $this->payload());

        $this->assertResponseOk();
        $this->assertResponseContains('No se pudo guardar el desarrollo.');
        $this->assertResponseNotContains('Desarrollo creado.');
        $this->assertSame($before, $this->countDevelopments());
    }

    // --- edit -------------------------------------------------------------------------------------------------

    public function testEditFormShowsTheStoredValuesToTheSuperAdministrator(): void
    {
        $this->signInSuper();

        $this->get('/developments/' . $this->devB . '/edit');

        $this->assertResponseOk();
        $this->assertResponseContains('Residencial Sur');
        $this->assertResponseContains('Banco Secreto');
        $this->assertResponseContains(self::BANK_ACCOUNT);
    }

    public function testSuperEditsEveryField(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devB . '/edit', $this->payload([
            'name' => 'Residencial Sur II', 'bank_account' => '999',
        ]));

        $this->assertRedirect('/developments');
        $this->assertFlashMessage('Desarrollo actualizado.');
        $row = $this->row('Residencial Sur II');
        $this->assertNotNull($row);
        $this->assertSame((int)$this->devB, (int)$row['id']);
        $this->assertSame('999', $row['bank_account']);
        $this->assertSame('Calle', $row['section_label']);
    }

    public function testEditCannotChangeTheArchiveState(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devB . '/edit', $this->payload(['archived_at' => '2020-01-01 00:00:00']));

        $this->assertNull($this->row('Lomas del Valle')['archived_at']);
    }

    public function testAnInvalidEditIsShownAgainAndNothingChanges(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devB . '/edit', $this->payload(['name' => '']));

        $this->assertResponseOk();
        $this->assertResponseContains('error-message');
        $this->assertNotNull($this->row('Residencial Sur'));
    }

    #[DataProvider('refusedActors')]
    public function testNobodyButTheSuperAdministratorMayEditEvenTheirOwnDevelopment(string $role, bool $readOnly): void
    {
        $this->signInWithRole($role, $readOnly);
        $this->enableCsrfToken();

        $this->get('/developments/' . $this->devA . '/edit');
        $this->assertResponseCode(404);

        $this->post('/developments/' . $this->devA . '/edit', $this->payload(['name' => 'Hacked']));
        $this->assertResponseCode(404);
        $this->assertNull($this->row('Hacked'));
        $this->assertNotNull($this->row('Residencial Norte'));
    }

    public function testEditingAnotherDevelopmentIsA404ThatLeaksNothing(): void
    {
        $this->signInWithRole('administrator', false);

        $this->get('/developments/' . $this->devB . '/edit');
        $foreign = [$this->_response->getStatusCode(), (string)$this->_response->getBody()];

        $this->get('/developments/987654/edit');
        $missing = [$this->_response->getStatusCode(), (string)$this->_response->getBody()];
        $foreign[1] = str_replace('/developments/' . $this->devB . '/edit', '/developments/N/edit', $foreign[1]);
        $missing[1] = str_replace('/developments/987654/edit', '/developments/N/edit', $missing[1]);

        $this->assertSame(404, $foreign[0]);
        $this->assertSame($missing, $foreign);
        $this->assertStringNotContainsString('Banco Secreto', $foreign[1]);
        $this->assertStringNotContainsString(self::BANK_ACCOUNT, $foreign[1]);
    }

    public function testEditRequiresACsrfToken(): void
    {
        $this->signInSuper();
        $this->_csrfToken = false;
        $this->_cookie = [];

        $this->post('/developments/' . $this->devB . '/edit', $this->payload());

        $this->assertResponseCode(403);
        $this->assertNotNull($this->row('Residencial Sur'));
    }

    public function testAFailedSaveOnEditIsAnErrorNotASuccess(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->useSaveThat(fn() => false);

        $this->post('/developments/' . $this->devB . '/edit', $this->payload(['name' => 'Changed']));

        $this->assertResponseOk();
        $this->assertResponseContains('No se pudo guardar el desarrollo.');
        $this->assertResponseNotContains('Desarrollo actualizado.');
        $this->assertNull($this->row('Changed'));
    }

    public function testAScopeViolationOnEditIsAnErrorNotASuccessOrA500(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->useSaveThat(fn() => throw new DevelopmentScopeViolation());

        $this->post('/developments/' . $this->devB . '/edit', $this->payload(['name' => 'Changed']));

        $this->assertResponseOk();
        $this->assertResponseContains('No se pudo guardar el desarrollo.');
        $this->assertResponseNotContains('Desarrollo actualizado.');
        $this->assertNull($this->row('Changed'));
    }

    public function testClearingAnOptionalFieldOnEditStoresNull(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devB . '/edit', $this->payload([
            'name' => 'Residencial Sur', 'bank_name' => '', 'bank_account' => '  ',
        ]));

        $this->assertRedirect('/developments');
        $row = $this->row('Residencial Sur');
        $this->assertNull($row['bank_name']);
        $this->assertNull($row['bank_account']);
        $this->assertSame('Av. Central 100', $row['address']);
    }

    public function testEditWithoutATimezoneKeepsTheStoredOne(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->db()->execute("UPDATE developments SET timezone = 'America/Tijuana' WHERE id = ?", [$this->devB]);
        $data = $this->payload(['name' => 'Residencial Sur II']);
        unset($data['timezone']);

        $this->post('/developments/' . $this->devB . '/edit', $data);

        $this->assertRedirect('/developments');
        $this->assertSame('America/Tijuana', $this->row('Residencial Sur II')['timezone']);
    }

    public function testAddWithoutATimezoneIsAValidationErrorNotASilentDefault(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $data = $this->payload();
        unset($data['timezone']);
        $before = $this->countDevelopments();

        $this->post('/developments/add', $data);

        $this->assertResponseOk();
        $this->assertResponseContains('error-message');
        $this->assertSame($before, $this->countDevelopments());
    }

    public function testAnArchivedDevelopmentCanStillBeEdited(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->db()->execute("UPDATE developments SET archived_at = '2020-01-02 03:04:05' WHERE id = ?", [$this->devB]);

        $this->post('/developments/' . $this->devB . '/edit', $this->payload(['name' => 'Renombrado']));

        $this->assertRedirect('/developments');
        $this->assertStringStartsWith('2020-01-02 03:04:05', (string)$this->archivedAt($this->devB));
        $this->assertSame($this->devB, (int)$this->row('Renombrado')['id']);
    }

    public function testAnInvalidEditShowsTheSubmittedValuesAndKeepsTheStoredRow(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devB . '/edit', $this->payload([
            'name' => '', 'address' => 'Calle Nueva 77',
        ]));

        $this->assertResponseOk();
        $this->assertResponseContains('Calle Nueva 77');
        $this->assertResponseContains('error-message');
        $row = $this->row('Residencial Sur');
        $this->assertNotNull($row);
        $this->assertNull($row['address']);
        $this->assertSame('Banco Secreto', $row['bank_name']);
    }

    // --- ids and non-scalar input -----------------------------------------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function idBoundActions(): array
    {
        return [
            'edit GET' => ['GET', 'edit'], 'edit POST' => ['POST', 'edit'],
            'archive' => ['POST', 'archive'], 'unarchive' => ['POST', 'unarchive'],
        ];
    }

    private function send(string $method, string $url): void
    {
        $method === 'POST' ? $this->post($url, $this->payload()) : $this->get($url);
    }

    #[DataProvider('idBoundActions')]
    public function testAMissingIdAnswersTheSame404AsAnOutOfScopeOne(string $method, string $action): void
    {
        $this->signInWithRole('administrator', false);
        $this->enableCsrfToken();

        $this->send($method, '/developments/' . $this->devB . '/' . $action);
        $foreign = [$this->_response->getStatusCode(), (string)$this->_response->getBody()];
        $this->send($method, '/developments/987654/' . $action);
        $missing = [$this->_response->getStatusCode(), (string)$this->_response->getBody()];
        $foreign[1] = str_replace('/developments/' . $this->devB . '/', '/developments/N/', $foreign[1]);
        $missing[1] = str_replace('/developments/987654/', '/developments/N/', $missing[1]);

        $this->assertSame(404, $missing[0]);
        $this->assertSame($foreign, $missing);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function malformedIds(): array
    {
        $cases = [];
        foreach (self::idBoundActions() as $name => [$method, $action]) {
            $cases[$name . ' non-numeric'] = [$method, $action, 'abc'];
            $cases[$name . ' 20 digits'] = [$method, $action, '99999999999999999999'];
            $cases[$name . ' leading zero'] = [$method, $action, '01'];
        }

        return $cases;
    }

    #[DataProvider('malformedIds')]
    public function testAMalformedIdIsA404EvenForTheSuperAdministrator(string $method, string $action, string $id): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->send($method, '/developments/' . $id . '/' . $action);

        $this->assertResponseCode(404);
        $this->assertSame(2, $this->countDevelopments());
    }

    /**
     * @return array<string, array{int, int|null, bool}>
     */
    public static function inactiveSuperAdministrators(): array
    {
        // startsInDays, endsInDays, readOnly
        return [
            'read-only' => [-30, null, true],
            'expired' => [-30, -1, false],
            'not started' => [5, null, false],
        ];
    }

    #[DataProvider('inactiveSuperAdministrators')]
    public function testASuperAdministratorWhoIsNotInForceCannotManage(
        int $startsInDays,
        ?int $endsInDays,
        bool $readOnly,
    ): void {
        $userId = $this->signInAs('root');
        $this->assignRole($userId, 'super_administrator', null, $startsInDays, $endsInDays, $readOnly);
        // An administrator assignment keeps the account usable, so the refusals below come from the permission.
        $this->assignRole($userId, 'administrator', $this->devA);
        $this->enableCsrfToken();
        $before = $this->countDevelopments();

        $this->get('/developments/add');
        $this->assertResponseCode(403);
        $this->post('/developments/add', $this->payload());
        $this->assertResponseCode(403);
        $this->post('/developments/' . $this->devA . '/edit', $this->payload(['name' => 'Hacked']));
        $this->assertResponseCode(404);
        $this->post('/developments/' . $this->devA . '/archive');
        $this->assertResponseCode(404);
        $this->assertSame($before, $this->countDevelopments());
        $this->assertNull($this->archivedAt($this->devA));
        $this->assertNotNull($this->row('Residencial Norte'));
    }

    public function testADisabledSuperAdministratorCannotManage(): void
    {
        $userId = $this->signInSuper();
        $this->db()->execute("UPDATE users SET status = 'disabled' WHERE id = ?", [$userId]);
        $this->enableCsrfToken();
        $before = $this->countDevelopments();

        $this->post('/developments/add', $this->payload());
        $this->assertContains($this->_response->getStatusCode(), [302, 403]);
        $this->post('/developments/' . $this->devA . '/archive');
        $this->assertContains($this->_response->getStatusCode(), [302, 403, 404]);

        $this->assertSame($before, $this->countDevelopments());
        $this->assertNull($this->archivedAt($this->devA));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function nonScalarInput(): array
    {
        return [
            'name' => [['name' => ['x']]],
            'timezone' => [['timezone' => ['x']]],
            'type' => [['type' => ['x']]],
            'address' => [['address' => ['x']]],
        ];
    }

    /**
     * @param array<string, mixed> $override Field replaced by an array.
     */
    #[DataProvider('nonScalarInput')]
    public function testNonScalarInputOnAddIsAValidationErrorNotA500(array $override): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $before = $this->countDevelopments();

        $this->post('/developments/add', $override + $this->payload());

        $this->assertResponseOk();
        $this->assertResponseContains('error-message');
        $this->assertSame($before, $this->countDevelopments());
    }

    /**
     * @param array<string, mixed> $override Field replaced by an array.
     */
    #[DataProvider('nonScalarInput')]
    public function testNonScalarInputOnEditIsAValidationErrorNotA500(array $override): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devB . '/edit', $override + $this->payload());

        $this->assertResponseOk();
        $this->assertResponseContains('error-message');
        $this->assertNotNull($this->row('Residencial Sur'));
    }

    // --- archive and unarchive --------------------------------------------------------------------------------

    private function archivedAt(int $id): mixed
    {
        return $this->db()->execute('SELECT archived_at FROM developments WHERE id = ?', [$id])->fetch()[0];
    }

    public function testSuperArchivesADevelopmentAndKeepsItsData(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devB . '/archive');

        $this->assertRedirect('/developments');
        $this->assertFlashMessage('Desarrollo archivado.');
        $this->assertNotNull($this->archivedAt($this->devB));
        $row = $this->row('Residencial Sur');
        $this->assertSame(self::BANK_ACCOUNT, $row['bank_account']);
        $this->assertNull($this->archivedAt($this->devA));
    }

    public function testSuperUnarchivesADevelopment(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->post('/developments/' . $this->devB . '/archive');

        $this->post('/developments/' . $this->devB . '/unarchive');

        $this->assertRedirect('/developments');
        $this->assertFlashMessage('Desarrollo restaurado.');
        $this->assertNull($this->archivedAt($this->devB));
    }

    public function testArchivingAnArchivedDevelopmentWritesNothingAndWarns(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->db()->execute("UPDATE developments SET archived_at = '2020-01-02 03:04:05' WHERE id = ?", [$this->devB]);

        $this->post('/developments/' . $this->devB . '/archive');

        $this->assertRedirect('/developments');
        $this->assertFlashElement('flash/warning');
        $this->assertFlashMessage('El desarrollo ya estaba archivado.');
        $this->assertStringStartsWith('2020-01-02 03:04:05', (string)$this->archivedAt($this->devB));
    }

    public function testRestoringAnActiveDevelopmentWritesNothingAndWarns(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devA . '/unarchive');

        $this->assertRedirect('/developments');
        $this->assertFlashElement('flash/warning');
        $this->assertFlashMessage('El desarrollo no está archivado.');
        $this->assertNull($this->archivedAt($this->devA));
    }

    public function testTheStateChangeReadsTheRowUnderALockSoConcurrentRequestsSerialize(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $epilogs = [];
        $listener = function (EventInterface $event, SelectQuery $query) use (&$epilogs): void {
            $epilog = $query->clause('epilog');
            $epilogs[] = $epilog instanceof ExpressionInterface ? $epilog->sql(new ValueBinder()) : (string)$epilog;
        };
        EventManager::instance()->on('Model.beforeFind', ['priority' => 1], $listener);

        try {
            $this->post('/developments/' . $this->devA . '/archive');
        } finally {
            EventManager::instance()->off('Model.beforeFind', $listener);
        }

        $this->assertNotNull($this->archivedAt($this->devA));
        $this->assertNotEmpty(array_filter($epilogs, fn(string $sql) => str_contains($sql, 'FOR UPDATE')));
    }

    #[DataProvider('refusedActors')]
    public function testNobodyButTheSuperAdministratorMayArchiveOrUnarchive(string $role, bool $readOnly): void
    {
        $this->signInWithRole($role, $readOnly);
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devB]);
        $this->enableCsrfToken();

        $this->post('/developments/' . $this->devA . '/archive');
        $this->assertResponseCode(404);
        $this->assertNull($this->archivedAt($this->devA));

        $this->post('/developments/' . $this->devB . '/unarchive');
        $this->assertResponseCode(404);
        $this->assertNotNull($this->archivedAt($this->devB));
    }

    public function testArchiveAndUnarchiveAreRoutedForPostOnly(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();

        foreach (['archive', 'unarchive'] as $action) {
            $this->get('/developments/' . $this->devA . '/' . $action);
            $this->assertResponseCode(404);
            $this->put('/developments/' . $this->devA . '/' . $action);
            $this->assertResponseCode(404);
        }
        $this->assertNull($this->archivedAt($this->devA));
    }

    public function testArchiveRequiresACsrfToken(): void
    {
        $this->signInSuper();
        $this->_csrfToken = false;
        $this->_cookie = [];

        $this->post('/developments/' . $this->devA . '/archive');

        $this->assertResponseCode(403);
        $this->assertNull($this->archivedAt($this->devA));
    }

    public function testAFailedArchiveSaveIsAnErrorNotASuccess(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->useSaveThat(fn() => false);

        $this->post('/developments/' . $this->devA . '/archive');

        $this->assertRedirect('/developments');
        $this->assertFlashMessage('No se pudo archivar el desarrollo.');
        $this->assertNull($this->archivedAt($this->devA));
    }

    public function testAScopeViolationOnUnarchiveIsAnErrorNotASuccess(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
        $this->useSaveThat(fn() => throw new DevelopmentScopeViolation());

        $this->post('/developments/' . $this->devA . '/unarchive');

        $this->assertRedirect('/developments');
        $this->assertFlashMessage('No se pudo restaurar el desarrollo.');
        $this->assertNotNull($this->archivedAt($this->devA));
    }

    // --- no delete --------------------------------------------------------------------------------------------

    public function testNoRouteDeletesADevelopment(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->get('/developments');

        foreach (Router::routes() as $route) {
            $controller = $route->defaults['controller'] ?? null;
            if ($controller === 'Developments') {
                $this->assertNotContains($route->defaults['action'] ?? '', ['delete', 'remove', 'destroy']);
            }
        }

        $this->delete('/developments/' . $this->devA);
        $this->assertResponseCode(404);
        $this->post('/developments/' . $this->devA . '/delete');
        $this->assertResponseCode(404);
        $this->assertSame(2, $this->countDevelopments());
    }

    // --- index ------------------------------------------------------------------------------------------------

    public function testTheListOffersManagementLinksOnlyToTheSuperAdministrator(): void
    {
        $this->signInSuper();

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseContains('Nuevo desarrollo');
        $this->assertResponseContains('Editar');
        $this->assertResponseContains('Archivar');
        $this->assertResponseNotContains('Restaurar');
    }

    public function testAnAdministratorSeesNoManagementLinksAndNoBankData(): void
    {
        $this->signInWithRole('administrator', false);

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseContains('Residencial Norte');
        $this->assertResponseNotContains('Nuevo desarrollo');
        $this->assertResponseNotContains('Editar');
        $this->assertResponseNotContains('Archivar');
        $this->assertResponseNotContains('Restaurar');
    }

    public function testTheListSelectsFieldsExplicitlyAndNeverLoadsBankData(): void
    {
        $userId = $this->signInAs('root');
        $this->assignRole($userId, 'super_administrator', null);

        $this->get('/developments');

        /** @var iterable<\App\Model\Entity\Development> $developments */
        $developments = $this->viewVariable('developments');
        $count = 0;
        foreach ($developments as $development) {
            $count++;
            $loaded = array_keys($development->toArray());
            foreach (['bank_account', 'bank_name', 'rfc', 'phone', 'address'] as $private) {
                $this->assertNotContains($private, $loaded);
            }
            foreach (['id', 'name', 'type', 'archived_at'] as $needed) {
                $this->assertContains($needed, $loaded);
            }
        }
        $this->assertSame(2, $count);
        $this->assertResponseNotContains(self::BANK_ACCOUNT);
        $this->assertResponseNotContains('Banco Secreto');
    }

    public function testTheListMarksArchivedDevelopments(): void
    {
        $this->signInSuper();
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devB]);

        $this->get('/developments');

        $this->assertResponseContains('Archivado');
        $this->assertResponseContains('Restaurar');
    }

    // --- helpers ----------------------------------------------------------------------------------------------

    /**
     * Make the save of a development fail the way the given behaviour says: it returns false (the save is refused)
     * or throws. Done from a `Model.beforeSave` listener, so the real table and its policy stay in place.
     *
     * @param callable $behaviour What the save does.
     * @return void
     */
    private function useSaveThat(callable $behaviour): void
    {
        $this->saveListener = function (EventInterface $event) use ($behaviour): void {
            if ($behaviour() === false) {
                $event->stopPropagation();
                $event->setResult(false);
            }
        };
        EventManager::instance()->on('Model.beforeSave', ['priority' => 1], $this->saveListener);
    }
}
