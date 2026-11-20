<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SignsInTrait;
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The people screens: list, add and edit (spec people).
 *
 * Every test signs in as a real role so that row-level security answers too; the checks that must hold because of
 * layer 1 (the scope the controller resolves, and reading the person before writing it) use users that row-level
 * security alone would let through: people with `people.view` or `people.manage` in both developments.
 */
class PeopleControllerTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SignsInTrait;

    private const ARCHIVED = 'El desarrollo está archivado: no se pueden guardar cambios.';
    private const REFUSED = 'No tienes permiso para modificar los datos de esta persona.';

    private int $devA;
    private int $devB;
    private mixed $debugBefore = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->devA = $this->createDevelopment('Residencial Norte');
        $this->devB = $this->createDevelopment('Residencial Sur');
        $this->debugBefore = Configure::read('debug');
        Configure::write('debug', false);
    }

    protected function tearDown(): void
    {
        Configure::write('debug', $this->debugBefore);
        parent::tearDown();
    }

    private function base(int|string $development): string
    {
        return '/developments/' . $development . '/people';
    }

    /**
     * @return int User id, signed in with the role in development A.
     */
    private function signInWith(string $role, bool $readOnly = false): int
    {
        $userId = $this->signInAs('ann');
        $this->assignRole($userId, $role, $this->devA, readOnly: $readOnly);

        return $userId;
    }

    /**
     * @param array<string, string> $overrides Fields to change.
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + ['name' => 'Ana Pérez', 'email' => 'ana@example.test', 'phone' => '555 0100', 'rfc' => 'xaxx010101000'];
    }

    /**
     * What a response tells the client, minus what always differs between two requests (see UnitsControllerTest).
     *
     * @return array{int, string, array<string, string>}
     */
    private function outcome(): array
    {
        $body = (string)$this->_response->getBody();
        $body = (string)preg_replace(
            ['#/developments/[^\'"<\s]*#', '#name="post_\w+"#', '#post_\w+#', '#value="[^"]{60,}"#'],
            ['<url>', 'name="<form>"', '<form>', 'value="<token>"'],
            $body,
        );
        $headers = [];
        foreach ($this->_response->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), ['set-cookie', 'date', 'content-length', 'x-csrf-token'], true)) {
                continue;
            }
            $headers[$name] = implode(', ', $values);
        }
        ksort($headers);

        return [$this->_response->getStatusCode(), $body, $headers];
    }

    private function peopleCount(): int
    {
        return (int)$this->db()->execute('SELECT COUNT(*) FROM people')->fetch()[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function personRow(string $name): ?array
    {
        $row = $this->db()->execute('SELECT * FROM people WHERE name = ?', [$name])->fetch('assoc');

        return $row === false ? null : $row;
    }

    /**
     * @return list<int> Developments the person is linked to.
     */
    private function linksOf(int $person): array
    {
        return array_map('intval', array_column(
            $this->db()->execute(
                'SELECT development_id FROM person_developments WHERE person_id = ? ORDER BY development_id',
                [$person],
            )->fetchAll('assoc') ?: [],
            'development_id',
        ));
    }

    private function archive(int $development): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$development]);
    }

    /**
     * @return array<string, array{string, bool, bool}> Role, read-only flag, may write.
     */
    public static function readers(): array
    {
        return [
            'administrator' => ['administrator', false, true],
            'data_entry' => ['data_entry', false, true],
            'accountant' => ['accountant', false, false],
            'supervisor' => ['supervisor', false, false],
            'read-only administrator' => ['administrator', true, false],
        ];
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function writeRefused(): array
    {
        return [
            'accountant' => ['accountant', false],
            'supervisor' => ['supervisor', false],
            'resident' => ['resident', false],
            'guard' => ['guard', false],
            'read-only administrator' => ['administrator', true],
            'read-only data_entry' => ['data_entry', true],
        ];
    }

    // --- list --------------------------------------------------------------------------------------------------

    public function testAnonymousVisitorIsSentToLogin(): void
    {
        $this->get($this->base($this->devA));

        $this->assertRedirectContains('/login');
    }

    #[DataProvider('readers')]
    public function testReadersSeeThePeopleOfTheirDevelopmentAndNoOneElse(string $role, bool $readOnly, bool $canWrite): void
    {
        $mine = $this->createPerson('Ana de Norte', [$this->devA]);
        $this->createPerson('Beto de Sur', [$this->devB]);
        $this->createPerson('Carla compartida', [$this->devA, $this->devB]);
        $this->db()->execute("UPDATE people SET email = 'ana@norte.test', phone = '555 1', rfc = 'RFCNORTE0001' WHERE id = ?", [$mine]);
        $this->signInWith($role, $readOnly);

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $this->assertResponseContains('Residencial Norte');
        $this->assertResponseContains('Ana de Norte');
        $this->assertResponseContains('ana@norte.test');
        $this->assertResponseContains('RFCNORTE0001');
        $this->assertResponseContains('Carla compartida');
        $this->assertResponseNotContains('Beto de Sur');
        if ($canWrite) {
            $this->assertResponseContains($this->base($this->devA) . '/add');
            $this->assertResponseContains($this->base($this->devA) . '/' . $mine . '/edit');
        } else {
            $this->assertResponseNotContains($this->base($this->devA) . '/add');
            $this->assertResponseNotContains('/edit');
        }
    }

    /**
     * The user may read in both developments, so row-level security lets the other development's people through:
     * only resolving the development of the URL keeps them off this list.
     */
    public function testAUserOfBothDevelopmentsSeesOnlyThePeopleOfTheOneInTheUrl(): void
    {
        $user = $this->signInWith('accountant');
        $this->assignRole($user, 'accountant', $this->devB);
        $this->createPerson('Ana de Norte', [$this->devA]);
        $this->createPerson('Beto de Sur', [$this->devB]);
        $this->createPerson('Carla compartida', [$this->devA, $this->devB]);

        $this->get($this->base($this->devA));
        $this->assertResponseContains('Ana de Norte');
        $this->assertResponseContains('Carla compartida');
        $this->assertResponseNotContains('Beto de Sur');

        $this->get($this->base($this->devB));
        $this->assertResponseContains('Beto de Sur');
        $this->assertResponseContains('Carla compartida');
        $this->assertResponseNotContains('Ana de Norte');
    }

    public function testTheSuperAdministratorSeesOnlyThePeopleOfTheDevelopmentInTheUrl(): void
    {
        $root = $this->signInAs('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->createPerson('Ana de Norte', [$this->devA]);
        $this->createPerson('Beto de Sur', [$this->devB]);

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $this->assertResponseContains('Ana de Norte');
        $this->assertResponseNotContains('Beto de Sur');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonReaders(): array
    {
        return ['resident' => ['resident'], 'guard' => ['guard']];
    }

    #[DataProvider('nonReaders')]
    public function testAResidentOrGuardIsRefusedTheListAndNeverSeesPersonalData(string $role): void
    {
        $person = $this->createPerson('Ana de Norte', [$this->devA]);
        $this->db()->execute("UPDATE people SET rfc = 'RFCNORTE0001' WHERE id = ?", [$person]);
        $this->signInWith($role);

        $this->get($this->base($this->devA));
        $this->assertResponseCode(403);
        $this->assertResponseNotContains('RFCNORTE0001');
        $this->assertResponseNotContains('Ana de Norte');

        $this->get($this->base($this->devA) . '/' . $person . '/edit');
        $this->assertResponseCode(403);
        $this->assertResponseNotContains('RFCNORTE0001');
    }

    public function testAUserWithNoAssignmentIsRefused(): void
    {
        $this->signInAs('ann');

        $this->get($this->base($this->devA));

        $this->assertResponseCode(403);
    }

    public function testForeignAndMissingDevelopmentsAnswerTheSame404(): void
    {
        $this->createPerson('Beto de Sur', [$this->devB]);
        $this->signInWith('administrator');

        $this->get($this->base($this->devB));
        $foreign = $this->outcome();
        $this->get($this->base(999999999));
        $missing = $this->outcome();
        $this->get($this->base('99999999999999999999'));
        $overflow = $this->outcome();

        $this->assertSame(404, $foreign[0]);
        $this->assertSame($missing, $foreign);
        $this->assertSame($missing, $overflow);
        $this->assertStringNotContainsString('Beto de Sur', $foreign[1]);
    }

    public function testListEscapesWhatPeopleTyped(): void
    {
        $this->createPerson('<script>alert(1)</script>', [$this->devA]);
        $this->signInWith('administrator');

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $this->assertResponseNotContains('<script>alert(1)</script>');
        $this->assertResponseContains('&lt;script&gt;');
    }

    // --- add ---------------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function writers(): array
    {
        return ['administrator' => ['administrator'], 'data_entry' => ['data_entry']];
    }

    #[DataProvider('writers')]
    public function testWritersCreateAPersonLinkedToTheDevelopmentWithoutAnyAccount(string $role): void
    {
        $this->signInWith($role);
        $users = (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0];
        $assignments = (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0];

        $this->get($this->base($this->devA) . '/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Nueva persona');

        $this->enableCsrfToken();
        $this->post($this->base($this->devA) . '/add', $this->payload());

        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('Persona creada.');
        $row = $this->personRow('Ana Pérez');
        $this->assertSame(['ana@example.test', '555 0100', 'XAXX010101000'], [$row['email'], $row['phone'], $row['rfc']]);
        $this->assertSame([$this->devA], $this->linksOf((int)$row['id']));
        $this->assertSame($users, (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0]);
        $this->assertSame($assignments, (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0]);
    }

    public function testOnlyTheNameIsRequired(): void
    {
        $this->signInWith('data_entry');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', ['name' => 'Solo Nombre', 'email' => '', 'phone' => '', 'rfc' => '']);

        $this->assertRedirect($this->base($this->devA));
        $row = $this->personRow('Solo Nombre');
        $this->assertSame([null, null, null], [$row['email'], $row['phone'], $row['rfc']]);
    }

    #[DataProvider('writeRefused')]
    public function testRefusedRolesAreRefusedTheAddFormAndNothingIsCreated(string $role, bool $readOnly): void
    {
        $this->signInWith($role, $readOnly);

        $this->get($this->base($this->devA) . '/add');
        $this->assertResponseCode(403);

        $this->enableCsrfToken();
        $this->post($this->base($this->devA) . '/add', $this->payload());
        $this->assertResponseCode(403);
        $this->assertSame(0, $this->peopleCount());
    }

    public function testAMissingNameIsAFieldErrorInSpanishAndNothingIsStored(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        foreach (['', '   '] as $name) {
            $this->post($this->base($this->devA) . '/add', $this->payload(['name' => $name]));

            $this->assertResponseOk();
            $this->assertResponseContains('Escribe el nombre.');
            $this->assertResponseContains('No se pudo guardar a la persona.');
        }
        $this->assertSame(0, $this->peopleCount());
    }

    public function testInvalidValuesAreFieldErrorsAndTheFormKeepsWhatWasTyped(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload(['email' => 'no-es-correo', 'rfc' => str_repeat('A', 14)]));

        $this->assertResponseOk();
        $this->assertResponseContains('Escribe un correo electrónico válido.');
        $this->assertResponseContains('El RFC no puede tener más de 13 caracteres.');
        $this->assertResponseContains('value="Ana Pérez"');
        $this->assertSame(0, $this->peopleCount());
    }

    public function testTheBodyCannotChooseTheKeyTheDevelopmentOrTheTimestamps(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload([
            'id' => '999', 'development_id' => (string)$this->devB, 'created' => '2000-01-01 00:00:00',
        ]));

        $this->assertRedirect($this->base($this->devA));
        $row = $this->personRow('Ana Pérez');
        $this->assertNotSame(999, (int)$row['id']);
        $this->assertSame([$this->devA], $this->linksOf((int)$row['id']));
        $this->assertStringStartsNotWith('2000', (string)$row['created']);
    }

    public function testAddInAForeignDevelopmentIs404AndCreatesNothing(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->get($this->base($this->devB) . '/add');
        $this->assertResponseCode(404);
        $this->post($this->base($this->devB) . '/add', $this->payload());
        $this->assertResponseCode(404);
        $this->post($this->base(999999999) . '/add', $this->payload());
        $this->assertResponseCode(404);
        $this->assertSame(0, $this->peopleCount());
    }

    /**
     * The user manages people in A and only reads in B: the add screen of B is a 404 (outside the manage scope), the
     * same as for a development that does not exist.
     */
    public function testAUserWhoOnlyReadsADevelopmentCannotAddThereEvenWhenTheyWriteElsewhere(): void
    {
        $user = $this->signInWith('data_entry');
        $this->assignRole($user, 'accountant', $this->devB);
        $this->enableCsrfToken();

        $this->get($this->base($this->devB));
        $this->assertResponseOk();
        $this->assertResponseNotContains($this->base($this->devB) . '/add');

        $this->post($this->base($this->devB) . '/add', $this->payload());
        $viaReadOnlyDevelopment = $this->outcome();
        $this->post($this->base(999999999) . '/add', $this->payload());
        $missing = $this->outcome();

        $this->assertSame(404, $viaReadOnlyDevelopment[0]);
        $this->assertSame($missing, $viaReadOnlyDevelopment);
        $this->assertSame(0, $this->peopleCount());
    }

    public function testAnArchivedDevelopmentRefusesToAddPeopleEvenToTheSuperAdministrator(): void
    {
        $root = $this->signInAs('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->archive($this->devA);
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/add');
        $this->assertResponseCode(403);
        $this->post($this->base($this->devA) . '/add', $this->payload());
        $this->assertResponseCode(403);
        $this->assertSame(0, $this->peopleCount());

        $this->get($this->base($this->devA));
        $this->assertResponseOk();
        $this->assertResponseNotContains($this->base($this->devA) . '/add');
    }

    /**
     * The development is archived between the policy check and the definer call: the database refuses with the one
     * answer it gives for every refusal, the request survives it (the failed call is rolled back on its own) and the
     * screen shows a message, not an error page.
     */
    public function testADevelopmentArchivedDuringTheRequestIsAMessageAndNothingIsStored(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $archive = function (): void {
            $this->archive($this->devA);
        };
        EventManager::instance()->on('Model.beforeMarshal', $archive);

        try {
            $this->post($this->base($this->devA) . '/add', $this->payload());
        } finally {
            EventManager::instance()->off('Model.beforeMarshal', $archive);
        }

        $this->assertResponseOk();
        $this->assertResponseContains('No se pudo crear a la persona en este desarrollo.');
        $this->assertResponseContains('value="Ana Pérez"');
        $this->assertSame(0, $this->peopleCount());
    }

    /**
     * No existence oracle: the email of a person of another development, the email of a person of this one and a new
     * email all get the same answer (people have no unique email or RFC, so nothing can differ).
     */
    public function testCreatingAPersonWhoseEmailOrRfcExistsElsewhereGivesTheSameOutcome(): void
    {
        $other = $this->createPerson('Beto de Sur', [$this->devB]);
        $own = $this->createPerson('Ana de Norte', [$this->devA]);
        $this->db()->execute("UPDATE people SET email = 'dup@example.test', rfc = 'RFCDUP000001' WHERE id IN (?, ?)", [$other, $own]);
        $this->signInWith('data_entry');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload(['name' => 'Uno', 'email' => 'new@example.test', 'rfc' => 'RFCNEW000001']));
        $fresh = $this->outcome();
        $this->post($this->base($this->devA) . '/add', $this->payload(['name' => 'Dos', 'email' => 'dup@example.test', 'rfc' => 'RFCDUP000001']));
        $duplicate = $this->outcome();

        $this->assertSame(302, $fresh[0]);
        $this->assertSame($fresh, $duplicate);
        $this->assertFlashMessage('Persona creada.');
        $this->assertNotNull($this->personRow('Dos'));
    }

    // --- edit --------------------------------------------------------------------------------------------------

    #[DataProvider('writers')]
    public function testWritersOpenAndEditAPersonOfTheirDevelopment(string $role): void
    {
        $person = $this->createPerson('Ana de Norte', [$this->devA]);
        $this->signInWith($role);

        $this->get($this->base($this->devA) . '/' . $person . '/edit');
        $this->assertResponseOk();
        $this->assertResponseContains('Editar persona');
        $this->assertResponseContains('value="Ana de Norte"');

        $this->enableCsrfToken();
        $this->post($this->base($this->devA) . '/' . $person . '/edit', ['name' => 'Ana de Norte', 'phone' => '555 0199', 'email' => '', 'rfc' => 'abc123']);

        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('Persona actualizada.');
        $row = $this->personRow('Ana de Norte');
        $this->assertSame(['555 0199', null, 'ABC123'], [$row['phone'], $row['email'], $row['rfc']]);
    }

    #[DataProvider('writeRefused')]
    public function testRefusedRolesAreRefusedTheEditAndNothingChanges(string $role, bool $readOnly): void
    {
        $person = $this->createPerson('Ana de Norte', [$this->devA]);
        $this->signInWith($role, $readOnly);

        $this->get($this->base($this->devA) . '/' . $person . '/edit');
        $this->assertResponseCode(403);

        $this->enableCsrfToken();
        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload(['name' => 'Cambiada']));
        $this->assertResponseCode(403);
        $this->assertNotNull($this->personRow('Ana de Norte'));
        $this->assertNull($this->personRow('Cambiada'));
    }

    public function testForeignAndMissingPeopleAnswerTheSame404(): void
    {
        $foreign = $this->createPerson('Beto de Sur', [$this->devB]);
        $this->signInWith('administrator');

        $this->get($this->base($this->devA) . '/' . $foreign . '/edit');
        $viaOwnPath = $this->outcome();
        $this->get($this->base($this->devB) . '/' . $foreign . '/edit');
        $viaForeignPath = $this->outcome();
        $this->get($this->base($this->devA) . '/' . ($foreign + 1000) . '/edit');
        $missing = $this->outcome();
        $this->get($this->base($this->devA) . '/99999999999999999999/edit');
        $overflow = $this->outcome();

        $this->assertSame(404, $viaOwnPath[0]);
        $this->assertSame($missing, $viaOwnPath);
        $this->assertSame($missing, $viaForeignPath);
        $this->assertSame($missing, $overflow);
        $this->assertStringNotContainsString('Beto de Sur', $viaOwnPath[1]);
    }

    /**
     * The user may read and write people in both developments, so row-level security and the scopes allow the
     * person: only reading it under the development of the URL (the read that precedes every write) keeps it from
     * being reached through the wrong one.
     */
    public function testAPersonIsNeverEditedThroughAnotherDevelopmentOfTheSameUser(): void
    {
        $user = $this->signInWith('administrator');
        $this->assignRole($user, 'administrator', $this->devB);
        $person = $this->createPerson('Beto de Sur', [$this->devB]);
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/' . $person . '/edit');
        $this->assertResponseCode(404);
        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload(['name' => 'Tomado']));
        $this->assertResponseCode(404);

        $this->assertNotNull($this->personRow('Beto de Sur'));
        $this->assertNull($this->personRow('Tomado'));
    }

    public function testASharedPersonIsRefusedToAnEditorOfOneDevelopmentWithAMessageThatRevealsNothing(): void
    {
        $person = $this->createPerson('Carla compartida', [$this->devA, $this->devB]);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/' . $person . '/edit');
        $this->assertResponseOk();

        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload(['name' => 'Carla cambiada']));

        $this->assertResponseOk();
        $this->assertResponseContains(self::REFUSED);
        $body = mb_strtolower((string)$this->_response->getBody());
        $this->assertStringNotContainsString('residencial sur', $body);
        $this->assertStringNotContainsString('otro desarrollo', $body);
        $this->assertStringNotContainsString('varios desarrollos', $body);
        $this->assertNotNull($this->personRow('Carla compartida'));
        $this->assertNull($this->personRow('Carla cambiada'));
        $this->assertSame([$this->devA, $this->devB], $this->linksOf($person));
    }

    public function testASharedPersonIsEditedByAnAdministratorOfEveryDevelopmentItIsLinkedTo(): void
    {
        $person = $this->createPerson('Carla compartida', [$this->devA, $this->devB]);
        $user = $this->signInWith('administrator');
        $this->assignRole($user, 'administrator', $this->devB);
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload(['name' => 'Carla cambiada']));

        $this->assertRedirect($this->base($this->devA));
        $this->assertNotNull($this->personRow('Carla cambiada'));
    }

    public function testAPersonLinkedToAnArchivedDevelopmentIsRefusedToItsEditors(): void
    {
        $archived = $this->createDevelopment('Residencial Archivo');
        $person = $this->createPerson('Carla compartida', [$this->devA, $archived]);
        $user = $this->signInWith('administrator');
        $this->assignRole($user, 'administrator', $archived);
        $this->archive($archived);
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload(['name' => 'Carla cambiada']));

        $this->assertResponseOk();
        $this->assertResponseContains(self::REFUSED);
        $this->assertNull($this->personRow('Carla cambiada'));
    }

    public function testEditInAnArchivedDevelopmentIsRefusedAndNothingChanges(): void
    {
        $person = $this->createPerson('Ana de Norte', [$this->devA]);
        $root = $this->signInAs('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->archive($this->devA);
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/' . $person . '/edit');
        $this->assertResponseCode(403);
        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload(['name' => 'Cambiada']));
        $this->assertResponseCode(403);
        $this->assertNull($this->personRow('Cambiada'));
    }

    public function testAMissingNameOnEditIsAFieldErrorAndTheRowIsKept(): void
    {
        $person = $this->createPerson('Ana de Norte', [$this->devA]);
        $this->signInWith('data_entry');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload(['name' => '']));

        $this->assertResponseOk();
        $this->assertResponseContains('Escribe el nombre.');
        $this->assertNotNull($this->personRow('Ana de Norte'));
    }

    public function testTheEditBodyCannotChangeTheKeyTheLinksOrTheTimestamps(): void
    {
        $person = $this->createPerson('Ana de Norte', [$this->devA]);
        $other = $this->createPerson('Beto de Sur', [$this->devB]);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $person . '/edit', $this->payload([
            'name' => 'Ana editada', 'id' => (string)$other, 'development_id' => (string)$this->devB,
            'created' => '2000-01-01 00:00:00',
        ]));

        $this->assertRedirect($this->base($this->devA));
        $this->assertSame('Beto de Sur', $this->personRow('Beto de Sur')['name']);
        $edited = $this->personRow('Ana editada');
        $this->assertSame($person, (int)$edited['id']);
        $this->assertSame([$this->devA], $this->linksOf($person));
        $this->assertStringStartsNotWith('2000', (string)$edited['created']);
    }

    public function testThereIsNoRouteToDeleteOrToLinkAPerson(): void
    {
        $person = $this->createPerson('Ana de Norte', [$this->devA]);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        foreach (['/delete', '/link', '/archive'] as $suffix) {
            $this->post($this->base($this->devA) . '/' . $person . $suffix, []);
            $this->assertResponseCode(404);
        }
        $this->post($this->base($this->devA) . '/link', ['person_id' => (string)$person]);
        $this->assertResponseCode(404);
        $this->assertSame(1, $this->peopleCount());
    }
}
