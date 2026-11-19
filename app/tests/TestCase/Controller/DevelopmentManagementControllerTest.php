<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\DevelopmentsController;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SignsInTrait;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
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
    private ?Closure $swapTable = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->devA = $this->createDevelopment('Residencial Norte');
        $this->devB = $this->createDevelopment('Residencial Sur');
        $this->db()->execute(
            'UPDATE developments SET bank_name = ?, bank_account = ? WHERE id = ?',
            ['Banco Secreto', self::BANK_ACCOUNT, $this->devB],
        );
        Configure::write('debug', false);
    }

    protected function tearDown(): void
    {
        if ($this->swapTable !== null) {
            EventManager::instance()->off('Model.beforeSave', $this->swapTable);
        }
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

    private function signInAs2(string $role, bool $readOnly): void
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
        $this->signInAs2($role, $readOnly);
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
        $this->signInAs2($role, $readOnly);
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
        $this->signInAs2('administrator', false);

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

    public function testArchivingTwiceKeepsTheFirstArchiveDate(): void
    {
        $this->signInSuper();
        $this->enableCsrfToken();
        $this->post('/developments/' . $this->devB . '/archive');
        $first = $this->archivedAt($this->devB);

        $this->post('/developments/' . $this->devB . '/archive');

        $this->assertRedirect('/developments');
        $this->assertSame($first, $this->archivedAt($this->devB));
    }

    #[DataProvider('refusedActors')]
    public function testNobodyButTheSuperAdministratorMayArchiveOrUnarchive(string $role, bool $readOnly): void
    {
        $this->signInAs2($role, $readOnly);
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
        $this->assertFalse(method_exists(DevelopmentsController::class, 'delete'));

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
        $this->assertResponseContains('/developments/add');
        $this->assertResponseContains('/developments/' . $this->devA . '/edit');
        $this->assertResponseContains('/developments/' . $this->devA . '/archive');
    }

    public function testAnAdministratorSeesNoManagementLinksAndNoBankData(): void
    {
        $this->signInAs2('administrator', false);

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseContains('Residencial Norte');
        $this->assertResponseNotContains('/developments/add');
        $this->assertResponseNotContains('/edit');
        $this->assertResponseNotContains('/archive');
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
            $this->assertEqualsCanonicalizing(
                ['id', 'name', 'type', 'archived_at'],
                array_keys($development->toArray()),
            );
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
        $this->assertResponseContains('/developments/' . $this->devB . '/unarchive');
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
        $this->swapTable = function (EventInterface $event) use ($behaviour): void {
            if ($behaviour() === false) {
                $event->stopPropagation();
                $event->setResult(false);
            }
        };
        EventManager::instance()->on('Model.beforeSave', ['priority' => 1], $this->swapTable);
    }
}
