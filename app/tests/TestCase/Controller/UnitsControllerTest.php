<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Authorization\DevelopmentScopeStack;
use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SignsInTrait;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\TestSuite\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The unit and section screens: list, open, add, edit, (de)activate, and sections created inline.
 *
 * Every test signs in as a real role so that row-level security answers too; the checks that must hold because of
 * layer 1 (the scope the controller resolves) use users that row-level security alone would let through.
 */
class UnitsControllerTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SignsInTrait;

    private int $devA;
    private int $devB;
    /**
     * @var list<\Closure>
     */
    private array $saveListeners = [];
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
        foreach ($this->saveListeners as $listener) {
            EventManager::instance()->off('Model.beforeSave', $listener);
        }
        Configure::write('debug', $this->debugBefore);
        parent::tearDown();
    }

    private function base(int|string $development): string
    {
        return '/developments/' . $development . '/units';
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
        return $overrides + [
            'number' => '12',
            'type' => 'apartment',
            'section_id' => '',
            'lot_area_m2' => '',
            'indiviso_pct' => '',
        ];
    }

    /**
     * What a response tells the client, minus what always differs between two requests: the requested address (the
     * not-found page echoes it), the CSRF token and form name of the layout, and the headers that carry a cookie, a
     * date or the length of a body that echoes the address.
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

    private function unitCount(?int $development = null): int
    {
        return (int)$this->db()->execute(
            'SELECT COUNT(*) FROM units WHERE (?::bigint IS NULL OR development_id = ?)',
            [$development, $development],
            ['integer', 'integer'],
        )->fetch()[0];
    }

    private function sectionCount(?int $development = null): int
    {
        return (int)$this->db()->execute(
            'SELECT COUNT(*) FROM sections WHERE (?::bigint IS NULL OR development_id = ?)',
            [$development, $development],
            ['integer', 'integer'],
        )->fetch()[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function unitRow(int $development, string $number): ?array
    {
        $row = $this->db()->execute(
            'SELECT * FROM units WHERE development_id = ? AND number = ?',
            [$development, $number],
        )->fetch('assoc');

        return $row === false ? null : $row;
    }

    private function archive(int $development): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$development]);
    }

    /**
     * Roles that read units, with their read-only flag, and whether they may also write.
     *
     * @return array<string, array{string, bool, bool}>
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
    public function testReadersSeeTheUnitsOfTheirDevelopment(string $role, bool $readOnly, bool $canWrite): void
    {
        $tower = $this->seedSection($this->devA, 'Torre 1');
        $this->seedUnit($this->devA, $tower, '101');
        $this->seedUnit($this->devB, null, '999-B');
        $this->signInWith($role, $readOnly);

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $this->assertResponseContains('Residencial Norte');
        $this->assertResponseContains('101');
        $this->assertResponseContains('Torre 1');
        $this->assertResponseNotContains('999-B');
        if ($canWrite) {
            $this->assertResponseContains($this->base($this->devA) . '/add');
        } else {
            $this->assertResponseNotContains($this->base($this->devA) . '/add');
        }
    }

    public function testResidentIsRefusedTheList(): void
    {
        $this->seedUnit($this->devA, null, '101');
        $this->signInWith('resident');

        $this->get($this->base($this->devA));

        $this->assertResponseCode(403);
        $this->assertResponseNotContains('101');
    }

    public function testScreensUseTheDevelopmentsOwnLabels(): void
    {
        $this->db()->execute(
            "UPDATE developments SET section_label = 'Bloque', number_label = 'Lote' WHERE id = ?",
            [$this->devA],
        );
        $block = $this->seedSection($this->devA, 'B-1');
        $unit = $this->seedUnit($this->devA, $block, '7');
        $this->db()->execute("UPDATE units SET type = 'house' WHERE id = ?", [$unit]);
        $this->signInWith('data_entry');

        $this->get($this->base($this->devA));
        $this->assertResponseContains('<th>Bloque</th>');
        $this->assertResponseContains('<th>Lote</th>');
        $this->assertResponseNotContains('Torre');
        $this->assertResponseNotContains('Departamento');

        $this->get($this->base($this->devA) . '/add');
        $this->assertResponseContains('Bloque');
        $this->assertResponseContains('Lote');
        $this->assertResponseNotContains('Torre');

        $this->get($this->base($this->devA) . '/' . $unit);
        $this->assertResponseContains('Bloque');
        $this->assertResponseContains('Lote');
        $this->assertResponseNotContains('Departamento');
    }

    public function testFieldErrorsAndFormsUseTheDevelopmentsOwnLabels(): void
    {
        $this->db()->execute(
            "UPDATE developments SET section_label = 'Bloque', number_label = 'Lote' WHERE id = ?",
            [$this->devA],
        );
        $block = $this->seedSection($this->devA, 'B-1');
        $this->seedUnit($this->devA, $block, '7');
        $other = $this->seedUnit($this->devA, null, '8');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->get($this->base($this->devA));
        $this->assertResponseContains('Nueva sección (Bloque)');

        foreach ([$this->base($this->devA) . '/add', $this->base($this->devA) . '/' . $other . '/edit'] as $form) {
            $this->get($form);
            $this->assertResponseContains('Sin bloque');
            $this->assertResponseContains('>Lote<');
            $this->assertResponseNotContains('Sin torre');
            $this->assertResponseNotContains('Departamento</label>');
        }

        $this->post($this->base($this->devA) . '/add', $this->payload(['number' => '7', 'section_id' => (string)$block]));
        $this->assertResponseContains('Ya existe una unidad con ese valor de «lote» para el mismo valor de «bloque» (o sin él).');
        $this->post($this->base($this->devA) . '/add', $this->payload(['number' => '', 'section_id' => 'abc']));
        $this->assertResponseContains('Escribe un valor para «lote».');
        $this->assertResponseContains('Elige un valor válido para «bloque».');
        $this->post($this->base($this->devA) . '/add', $this->payload(['section_id' => '99999']));
        $this->assertResponseContains('El valor de «bloque» no existe en este desarrollo.');
        $this->assertResponseNotContains('torre');
        $this->assertResponseNotContains('departamento');
    }

    public function testListShowsMixedTypesAndInactiveUnits(): void
    {
        $this->seedUnit($this->devA, null, 'C-1');
        $this->db()->execute(
            "INSERT INTO units (development_id, number, type, is_active, created, modified)
             VALUES (?, 'H-1', 'house', false, NOW(), NOW()), (?, 'L-1', 'lot', true, NOW(), NOW())",
            [$this->devA, $this->devA],
        );
        $this->signInWith('administrator');

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $this->assertResponseContains('Departamento');
        $this->assertResponseContains('Casa');
        $this->assertResponseContains('Lote');
        $this->assertResponseContains('Inactiva');
    }

    public function testListEscapesStoredValues(): void
    {
        $section = $this->seedSection($this->devA, '<b>Torre</b>');
        $this->seedUnit($this->devA, $section, '<script>alert(1)</script>');
        $this->signInWith('administrator');

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $this->assertResponseNotContains('<script>alert(1)</script>');
        $this->assertResponseNotContains('<b>Torre</b>');
        $this->assertResponseContains('&lt;script&gt;');
    }

    public function testForeignAndMissingDevelopmentsAnswerTheSame404(): void
    {
        $this->seedUnit($this->devB, null, '999-B');
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
        $this->assertStringNotContainsString('999-B', $foreign[1]);
    }

    public function testListIsPaginated(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            $this->db()->execute(
                "INSERT INTO units (development_id, number, type, created, modified)
                 VALUES (?, ?, 'apartment', NOW(), NOW())",
                [$this->devA, sprintf('U%03d', $i)],
            );
        }
        $this->signInWith('administrator');

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('U001', $body);
        $this->assertStringContainsString('U100', $body);
        $this->assertStringNotContainsString('U101', $body);
        $this->assertLessThan(strpos($body, 'U002'), strpos($body, 'U001'), 'ordered by number');
        $this->assertStringContainsString('page=2', $body);

        $this->get($this->base($this->devA) . '?page=2');
        $second = (string)$this->_response->getBody();
        $this->assertStringContainsString('U101', $second);
        $this->assertStringContainsString('U120', $second);
        $this->assertStringNotContainsString('U100', $second);
    }

    // --- open --------------------------------------------------------------------------------------------------

    public function testUnitIsOpenedWithItsDetails(): void
    {
        $section = $this->seedSection($this->devA, 'Torre 1');
        $this->db()->execute(
            "INSERT INTO units (development_id, section_id, number, type, lot_area_m2, indiviso_pct, created, modified)
             VALUES (?, ?, '5-C', 'house', 120.50, 1.2500, NOW(), NOW())",
            [$this->devA, $section],
        );
        $unit = (int)$this->unitRow($this->devA, '5-C')['id'];
        $this->signInWith('accountant');

        $this->get($this->base($this->devA) . '/' . $unit);

        $this->assertResponseOk();
        $this->assertResponseContains('5-C');
        $this->assertResponseContains('Casa');
        $this->assertResponseContains('Torre 1');
        $this->assertResponseContains('120.50');
        $this->assertResponseContains('1.2500');
        $this->assertResponseContains('Activa');
    }

    public function testForeignUnitAnswersTheSame404AsAMissingOne(): void
    {
        $foreign = $this->seedUnit($this->devB, null, '999-B');
        $own = $this->seedUnit($this->devA, null, '1');
        $this->signInWith('administrator');

        $this->get($this->base($this->devA) . '/' . $foreign);
        $viaOwnPath = $this->outcome();
        $this->get($this->base($this->devB) . '/' . $foreign);
        $viaForeignPath = $this->outcome();
        $this->get($this->base($this->devA) . '/' . ($foreign + 1000));
        $missing = $this->outcome();
        $this->get($this->base($this->devB) . '/' . $own);
        $ownViaForeignPath = $this->_response->getStatusCode();

        $this->assertSame(404, $viaOwnPath[0]);
        $this->assertSame($missing, $viaOwnPath);
        $this->assertSame($missing, $viaForeignPath);
        $this->assertSame(404, $ownViaForeignPath);
        $this->assertStringNotContainsString('999-B', $viaOwnPath[1]);
    }

    /**
     * The user may read and write in both developments, so row-level security and the scopes allow the unit: only
     * looking the row up by the development of the URL keeps it from answering under the wrong one.
     */
    public function testAUnitIsNeverReachedThroughAnotherDevelopmentOfTheSameUser(): void
    {
        $user = $this->signInWith('administrator');
        $this->assignRole($user, 'administrator', $this->devB);
        $unit = $this->seedUnit($this->devB, null, 'B-1');
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/' . $unit);
        $this->assertResponseCode(404);
        $this->get($this->base($this->devA) . '/' . $unit . '/edit');
        $this->assertResponseCode(404);
        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload(['number' => 'Z']));
        $this->assertResponseCode(404);
        $this->post($this->base($this->devA) . '/' . $unit . '/deactivate');
        $this->assertResponseCode(404);

        $row = $this->unitRow($this->devB, 'B-1');
        $this->assertTrue((bool)$row['is_active']);
        $this->get($this->base($this->devB) . '/' . $unit);
        $this->assertResponseOk();

        // The answer is the one for a unit that does not exist, headers included.
        $missing = $unit + 1000;
        foreach (['get' => '', 'get ' => '/edit', 'post' => '/edit', 'post ' => '/deactivate'] as $verb => $suffix) {
            $method = trim($verb);
            $this->$method($this->base($this->devA) . '/' . $unit . $suffix, $method === 'post' ? $this->payload() : []);
            $foreign = $this->outcome();
            $this->$method($this->base($this->devA) . '/' . $missing . $suffix, $method === 'post' ? $this->payload() : []);
            $this->assertSame($this->outcome(), $foreign, $verb . $suffix);
            $this->assertSame(404, $foreign[0]);
        }
    }

    /**
     * Same user in both developments: nothing is hidden by row-level security or by the scopes, so what makes the
     * answer for a development that does not exist the same as for an existing one is only the way it is built.
     */
    public function testAMissingDevelopmentLooksLikeAnExistingOneForAUserOfBoth(): void
    {
        $user = $this->signInWith('administrator');
        $this->assignRole($user, 'administrator', $this->devB);
        $this->enableCsrfToken();

        $this->get($this->base($this->devB));
        $this->assertResponseOk();
        $this->get($this->base(999999999));
        $this->assertResponseCode(404);
        $missingList = $this->outcome();
        $this->get($this->base('0'));
        $this->assertSame($missingList, $this->outcome());
        $this->post($this->base(999999999) . '/add', $this->payload());
        $missingAdd = $this->outcome();
        $this->post($this->base(999999999) . '/sections', ['name' => 'X']);
        $missingSection = $this->outcome();
        $this->assertSame(404, $missingAdd[0]);
        $this->assertSame(404, $missingSection[0]);
        $this->assertSame([], $missingAdd[2] === $missingSection[2] ? [] : ['headers differ']);
    }

    public function testAnOverflowingUnitIdIsTheSame404AsAMissingOne(): void
    {
        $this->signInWith('administrator');

        $this->get($this->base($this->devA) . '/99999999999999999999');
        $overflow = $this->outcome();
        $this->get($this->base($this->devA) . '/999999999');

        $this->assertSame(404, $overflow[0]);
        $this->assertSame($this->outcome(), $overflow);
    }

    public function testResidentCannotOpenAUnit(): void
    {
        $unit = $this->seedUnit($this->devA, null, '101');
        $this->signInWith('resident');

        $this->get($this->base($this->devA) . '/' . $unit);

        $this->assertResponseCode(403);
    }

    // --- add ---------------------------------------------------------------------------------------------------

    public function testAddFormOffersOnlySectionsOfThisDevelopment(): void
    {
        $this->seedSection($this->devA, 'Torre 1');
        $this->seedSection($this->devB, 'Torre Ajena');
        $this->signInWith('data_entry');

        $this->get($this->base($this->devA) . '/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Torre 1');
        $this->assertResponseNotContains('Torre Ajena');
    }

    #[DataProvider('readers')]
    public function testOnlyWritersCreateUnits(string $role, bool $readOnly, bool $canWrite): void
    {
        $this->signInWith($role, $readOnly);
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload());

        if ($canWrite) {
            $this->assertRedirect($this->base($this->devA));
            $this->assertNotNull($this->unitRow($this->devA, '12'));
        } else {
            $this->assertResponseCode(403);
            $this->assertSame(0, $this->unitCount());
        }
    }

    #[DataProvider('writeRefused')]
    public function testRefusedRolesAreRefusedTheAddForm(string $role, bool $readOnly): void
    {
        $this->signInWith($role, $readOnly);

        $this->get($this->base($this->devA) . '/add');

        $this->assertResponseCode(403);
    }

    public function testDataEntryCreatesAUnitWithoutAnyOccupancy(): void
    {
        $this->signInWith('data_entry');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload());

        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('Unidad creada.');
        $row = $this->unitRow($this->devA, '12');
        $this->assertSame('apartment', $row['type']);
        $this->assertNull($row['section_id']);
        $this->assertNull($row['lot_area_m2']);
        $this->assertTrue((bool)$row['is_active']);
    }

    public function testAHouseAnApartmentAndALotLiveInOneDevelopment(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        foreach (['house' => 'H', 'apartment' => 'A', 'lot' => 'L'] as $type => $number) {
            $this->post($this->base($this->devA) . '/add', $this->payload(['number' => $number, 'type' => $type]));
            $this->assertRedirect($this->base($this->devA));
        }

        $this->assertSame(3, $this->unitCount($this->devA));
    }

    public function testTheSameNumberInAnotherSectionOrDevelopmentIsAccepted(): void
    {
        $one = $this->seedSection($this->devA, 'Torre 1');
        $two = $this->seedSection($this->devA, 'Torre 2');
        $this->seedUnit($this->devA, $one, '12');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload(['section_id' => (string)$two]));

        $this->assertRedirect($this->base($this->devA));
        $this->assertSame(2, $this->unitCount($this->devA));
    }

    public function testDuplicateNumberWithoutSectionIsRejectedAsAFieldError(): void
    {
        $this->seedUnit($this->devA, null, '12');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload());

        $this->assertResponseOk();
        $this->assertResponseContains('Ya existe una unidad con ese valor de «departamento» para el mismo valor de «torre» (o sin él).');
        $this->assertResponseContains('No se pudo guardar la unidad.');
        $this->assertSame(1, $this->unitCount($this->devA));
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function invalidInputs(): array
    {
        return [
            'zero area' => [['lot_area_m2' => '0'], 'El valor debe ser mayor que cero.'],
            'negative area' => [['lot_area_m2' => '-5'], 'El valor debe ser mayor que cero.'],
            'indiviso over 100' => [['indiviso_pct' => '120'], 'El valor no puede ser mayor que 100.'],
            'area above the column' => [['lot_area_m2' => '10000000000'], 'El valor es demasiado grande.'],
            'area rounding above the column' => [['lot_area_m2' => '9999999999.995'], 'El valor es demasiado grande.'],
            'indiviso just above 100' => [['indiviso_pct' => '100.5'], 'El valor no puede ser mayor que 100.'],
            'indiviso zero' => [['indiviso_pct' => '0'], 'El valor debe ser mayor que cero.'],
            'not a number' => [['lot_area_m2' => 'abc'], 'El valor debe ser un número.'],
            'blank number' => [['number' => '   '], 'Escribe un valor para «departamento».'],
            'long number' => [['number' => str_repeat('9', 51)], 'No puede tener más de 50 caracteres.'],
            'unknown type' => [['type' => 'castle'], 'Elige un tipo válido.'],
            'missing type' => [['type' => ''], 'Elige un tipo válido.'],
            'non-numeric section' => [['section_id' => 'abc'], 'Elige un valor válido para «torre».'],
        ];
    }

    /**
     * @param array<string, string> $input Fields to override.
     */
    #[DataProvider('invalidInputs')]
    public function testInvalidValuesAreFieldErrorsInSpanishAndNothingIsStored(array $input, string $message): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload($input));

        $this->assertResponseOk();
        $this->assertResponseContains($message);
        $this->assertSame(0, $this->unitCount());
    }

    public function testApartmentWithoutAreaIsAccepted(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload(['lot_area_m2' => '', 'indiviso_pct' => '']));

        $this->assertRedirect($this->base($this->devA));
    }

    public function testTheLargestAcceptedMeasurementsAreStored(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post(
            $this->base($this->devA) . '/add',
            $this->payload(['lot_area_m2' => '9999999999.99', 'indiviso_pct' => '100']),
        );

        $this->assertRedirect($this->base($this->devA));
        $row = $this->unitRow($this->devA, '12');
        $this->assertSame('9999999999.99', $row['lot_area_m2']);
        $this->assertSame('100.0000', $row['indiviso_pct']);
    }

    public function testMeasurementsAreStored(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post(
            $this->base($this->devA) . '/add',
            $this->payload(['lot_area_m2' => '250.5', 'indiviso_pct' => '1.25']),
        );

        $row = $this->unitRow($this->devA, '12');
        $this->assertSame('250.50', $row['lot_area_m2']);
        $this->assertSame('1.2500', $row['indiviso_pct']);
    }

    public function testASectionOfAnotherDevelopmentIsRejectedLikeAMissingOne(): void
    {
        $foreign = $this->seedSection($this->devB, 'Torre Ajena');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload(['section_id' => (string)$foreign]));
        $foreignBody = $this->outcome();
        $this->assertResponseOk();
        $this->assertResponseContains('El valor de «torre» no existe en este desarrollo.');
        $this->post($this->base($this->devA) . '/add', $this->payload(['section_id' => (string)($foreign + 1000)]));
        $missingBody = $this->outcome();

        $this->assertSame(0, $this->unitCount());
        $this->assertSame(
            preg_replace('/value="\d+"/', '', $foreignBody[1]),
            preg_replace('/value="\d+"/', '', $missingBody[1]),
        );
    }

    /**
     * The user administers both developments, so row-level security shows the section of B. Only the section being
     * required to belong to the development of the unit (and the form listing only that development's sections)
     * keeps it out; the rule must refuse before the database does, with the message for a missing section.
     */
    public function testASectionOfAnotherDevelopmentOfTheSameUserIsRefusedByTheRuleNotTheDatabase(): void
    {
        $user = $this->signInWith('administrator');
        $this->assignRole($user, 'administrator', $this->devB);
        $foreign = $this->seedSection($this->devB, 'Torre Ajena');
        $this->enableCsrfToken();
        $reachedSave = false;
        $this->useSaveThat(function () use (&$reachedSave): void {
            $reachedSave = true;
        });

        $this->get($this->base($this->devA) . '/add');
        $this->assertResponseNotContains('Torre Ajena');

        $this->post($this->base($this->devA) . '/add', $this->payload(['section_id' => (string)$foreign]));
        $foreignOutcome = $this->outcome();
        $this->post($this->base($this->devA) . '/add', $this->payload(['section_id' => (string)($foreign + 1000)]));
        $missingOutcome = $this->outcome();

        $this->assertResponseContains('El valor de «torre» no existe en este desarrollo.');
        $this->assertFalse($reachedSave, 'the rule must refuse before the save reaches the database');
        $this->assertSame(0, $this->unitCount());
        $strip = fn(array $o): string => (string)preg_replace('/value="\d+"/', '', $o[1]);
        $this->assertSame($strip($missingOutcome), $strip($foreignOutcome));
        $this->assertSame($missingOutcome[2], $foreignOutcome[2]);
    }

    public function testABodyDevelopmentIdIsIgnored(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/add', $this->payload(['development_id' => (string)$this->devB]));

        $this->assertRedirect($this->base($this->devA));
        $this->assertNotNull($this->unitRow($this->devA, '12'));
        $this->assertSame(0, $this->unitCount($this->devB));
    }

    public function testAddInAForeignDevelopmentIs404AndWritesNothing(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devB) . '/add', $this->payload());
        $foreign = $this->outcome();
        $this->post($this->base(999999999) . '/add', $this->payload());
        $missing = $this->outcome();
        $this->get($this->base($this->devB) . '/add');
        $form = $this->_response->getStatusCode();

        $this->assertSame(404, $foreign[0]);
        $this->assertSame($missing, $foreign);
        $this->assertSame(404, $form);
        $this->assertSame(0, $this->unitCount());
    }

    /**
     * Row-level security alone would let this user read development B: only the `units.manage` scope the
     * controller resolves keeps them out.
     */
    public function testWritesUseTheManageScopeNotTheViewScope(): void
    {
        $user = $this->signInWith('data_entry');
        $this->assignRole($user, 'supervisor', $this->devB);
        $other = $this->seedUnit($this->devB, null, '1');
        $this->enableCsrfToken();

        $this->get($this->base($this->devB));
        $this->assertResponseOk();

        $this->post($this->base($this->devB) . '/add', $this->payload());
        $this->assertResponseCode(404);
        $this->get($this->base($this->devB) . '/add');
        $this->assertResponseCode(404);
        $this->post($this->base($this->devB) . '/sections', ['name' => 'Torre X']);
        $this->assertResponseCode(404);
        $this->post($this->base($this->devB) . '/' . $other . '/deactivate');
        $this->assertResponseCode(403);
        $this->post($this->base($this->devB) . '/' . $other . '/edit', $this->payload(['number' => '2']));
        $this->assertResponseCode(403);

        $this->assertSame(1, $this->unitCount($this->devB));
        $this->assertSame(0, $this->sectionCount($this->devB));
        $this->assertSame('1', $this->db()->execute('SELECT number FROM units WHERE id = ?', [$other])->fetch()[0]);
    }

    /**
     * The save runs under the `units.manage` scope: a user who can only read development B has it in the view scope
     * but not in the scope a write runs under.
     */
    public function testTheSaveRunsUnderTheManageScopeNotTheViewScope(): void
    {
        $user = $this->signInWith('data_entry');
        $this->assignRole($user, 'supervisor', $this->devB);
        $this->enableCsrfToken();
        $seen = null;
        $this->useSaveThat(function () use (&$seen): void {
            $seen ??= DevelopmentScopeStack::current()?->ids();
        });

        $this->post($this->base($this->devA) . '/add', $this->payload());
        $this->assertRedirect($this->base($this->devA));
        $this->assertSame([$this->devA], $seen);

        $seen = null;
        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 1']);
        $this->assertSame([$this->devA], $seen);
    }

    public function testAnArchivedDevelopmentRefusesTheAddForm(): void
    {
        $this->archive($this->devA);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/add');
        $this->assertResponseCode(403);
        $this->post($this->base($this->devA) . '/add', $this->payload());
        $this->assertResponseCode(403);

        $this->assertSame(0, $this->unitCount());
    }

    public function testAnArchivedDevelopmentStillListsItsUnitsWithoutWriteLinks(): void
    {
        $this->seedUnit($this->devA, null, '101');
        $this->archive($this->devA);
        $this->signInWith('administrator');

        $this->get($this->base($this->devA));

        $this->assertResponseOk();
        $this->assertResponseContains('101');
        $this->assertResponseNotContains($this->base($this->devA) . '/add');
        $this->assertResponseNotContains('Nueva sección');
    }

    public function testADevelopmentArchivedDuringTheSaveIsShownAsArchivedNotAsAFieldError(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $archived = false;
        $this->useSaveThat(function () use (&$archived): void {
            if (!$archived) {
                $archived = true;
                $this->archive($this->devA);
            }
        });

        $this->post($this->base($this->devA) . '/add', $this->payload());

        $this->assertTrue($archived);
        $this->assertResponseOk();
        $this->assertResponseContains('El desarrollo está archivado: no se pueden guardar cambios.');
        $this->assertResponseNotContains('development_id');
        $this->assertSame(0, $this->unitCount());
    }

    public function testADuplicateThatOnlyTheDatabaseSeesIsAFieldError(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $done = false;
        $this->useSaveThat(function () use (&$done): void {
            if (!$done) {
                $done = true;
                $this->seedUnit($this->devA, null, '12');
            }
        });

        $this->post($this->base($this->devA) . '/add', $this->payload());

        $this->assertTrue($done);
        $this->assertResponseOk();
        $this->assertResponseContains('Ya existe una unidad con ese valor de «departamento» para el mismo valor de «torre» (o sin él).');
        $this->assertSame(1, $this->unitCount($this->devA));
    }

    // --- edit --------------------------------------------------------------------------------------------------

    public function testEditFormIsPrefilled(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('data_entry');

        $this->get($this->base($this->devA) . '/' . $unit . '/edit');

        $this->assertResponseOk();
        $this->assertResponseContains('value="A-1"');
    }

    public function testDataEntryEditsAUnit(): void
    {
        $one = $this->seedSection($this->devA, 'Torre 1');
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('data_entry');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload([
            'number' => 'A-2', 'type' => 'house', 'section_id' => (string)$one, 'lot_area_m2' => '90',
        ]));

        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('Unidad actualizada.');
        $row = $this->unitRow($this->devA, 'A-2');
        $this->assertSame($unit, (int)$row['id']);
        $this->assertSame('house', $row['type']);
        $this->assertSame($one, (int)$row['section_id']);
        $this->assertSame('90.00', $row['lot_area_m2']);
    }

    public function testEditCannotChangeTheDevelopmentOrTheActiveFlag(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload([
            'number' => 'A-1', 'development_id' => (string)$this->devB, 'is_active' => '0', 'id' => '99999',
        ]));

        $this->assertRedirect($this->base($this->devA));
        $row = $this->unitRow($this->devA, 'A-1');
        $this->assertSame($unit, (int)$row['id']);
        $this->assertTrue((bool)$row['is_active']);
        $this->assertSame(0, $this->unitCount($this->devB));
    }

    public function testASectionDeletedDuringAnEditIsAFieldErrorOnTheSection(): void
    {
        $one = $this->seedSection($this->devA, 'Torre 1');
        $two = $this->seedSection($this->devA, 'Torre 2');
        $unit = $this->seedUnit($this->devA, $one, 'A-1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $done = false;
        $this->useSaveThat(function () use (&$done, $two): void {
            if (!$done) {
                $done = true;
                $this->db()->execute('DELETE FROM sections WHERE id = ?', [$two]);
            }
        });

        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload([
            'number' => 'A-1', 'section_id' => (string)$two,
        ]));

        $this->assertTrue($done);
        $this->assertResponseOk();
        $this->assertResponseContains('El valor de «torre» no existe en este desarrollo.');
        $this->assertSame($one, (int)$this->unitRow($this->devA, 'A-1')['section_id']);
    }

    public function testADevelopmentArchivedDuringAnEditIsShownAsArchived(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $this->useSaveThat(function (): void {
            $this->archive($this->devA);
        });

        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload(['number' => 'Z']));

        $this->assertResponseOk();
        $this->assertResponseContains('El desarrollo está archivado: no se pueden guardar cambios.');
        $this->assertSame('A-1', $this->db()->execute('SELECT number FROM units WHERE id = ?', [$unit])->fetch()[0]);
    }

    public function testADevelopmentArchivedDuringADeactivationIsShownAsArchived(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $this->useSaveThat(function (): void {
            $this->archive($this->devA);
        });

        $this->post($this->base($this->devA) . '/' . $unit . '/deactivate');

        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('El desarrollo está archivado: no se pueden guardar cambios.');
        $this->assertTrue((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    public function testEditRejectsADuplicateNumberAndKeepsTheRow(): void
    {
        $this->seedUnit($this->devA, null, 'A-1');
        $unit = $this->seedUnit($this->devA, null, 'A-2');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload(['number' => 'A-1']));

        $this->assertResponseOk();
        $this->assertResponseContains('Ya existe una unidad con ese valor de «departamento» para el mismo valor de «torre» (o sin él).');
        $this->assertSame('A-2', $this->db()->execute('SELECT number FROM units WHERE id = ?', [$unit])->fetch()[0]);
    }

    #[DataProvider('writeRefused')]
    public function testRefusedRolesCannotEdit(string $role, bool $readOnly): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith($role, $readOnly);
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/' . $unit . '/edit');
        $this->assertResponseCode(403);
        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload(['number' => 'Z']));
        $this->assertResponseCode(403);

        $this->assertSame('A-1', $this->db()->execute('SELECT number FROM units WHERE id = ?', [$unit])->fetch()[0]);
    }

    public function testEditOfAForeignUnitIs404LikeAMissingOne(): void
    {
        $foreign = $this->seedUnit($this->devB, null, 'B-1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $foreign . '/edit', $this->payload(['number' => 'Z']));
        $viaOwnPath = $this->outcome();
        $this->post($this->base($this->devB) . '/' . $foreign . '/edit', $this->payload(['number' => 'Z']));
        $viaForeignPath = $this->_response->getStatusCode();
        $this->post($this->base($this->devA) . '/' . ($foreign + 1000) . '/edit', $this->payload(['number' => 'Z']));
        $missing = $this->outcome();

        $this->assertSame(404, $viaOwnPath[0]);
        $this->assertSame($missing, $viaOwnPath);
        $this->assertSame(404, $viaForeignPath);
        $this->assertSame('B-1', $this->db()->execute('SELECT number FROM units WHERE id = ?', [$foreign])->fetch()[0]);
    }

    public function testEditInAnArchivedDevelopmentIsRefused(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->archive($this->devA);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->get($this->base($this->devA) . '/' . $unit . '/edit');
        $this->assertResponseCode(403);
        $this->post($this->base($this->devA) . '/' . $unit . '/edit', $this->payload(['number' => 'Z']));
        $this->assertResponseCode(403);

        $this->assertSame('A-1', $this->db()->execute('SELECT number FROM units WHERE id = ?', [$unit])->fetch()[0]);
    }

    // --- activate / deactivate ---------------------------------------------------------------------------------

    public function testDeactivateAndReactivateKeepTheRow(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('data_entry');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/deactivate');
        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('Unidad desactivada.');
        $this->assertFalse((bool)$this->unitRow($this->devA, 'A-1')['is_active']);

        $this->post($this->base($this->devA) . '/' . $unit . '/deactivate');
        $this->assertFlashMessage('La unidad ya estaba inactiva.');

        $this->post($this->base($this->devA) . '/' . $unit . '/activate');
        $this->assertFlashMessage('Unidad reactivada.');
        $this->assertTrue((bool)$this->unitRow($this->devA, 'A-1')['is_active']);

        $this->post($this->base($this->devA) . '/' . $unit . '/activate');
        $this->assertFlashMessage('La unidad ya estaba activa.');
        $this->assertSame(1, $this->unitCount($this->devA));
    }

    public function testDeactivateIsPostOnly(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('administrator');

        $this->get($this->base($this->devA) . '/' . $unit . '/deactivate');

        $this->assertResponseCode(404);
        $this->assertTrue((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    public function testDeactivateNeedsTheCsrfToken(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith('administrator');
        $this->_csrfToken = false;

        $this->post($this->base($this->devA) . '/' . $unit . '/deactivate');

        $this->assertResponseCode(403);
        $this->assertTrue((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    #[DataProvider('writeRefused')]
    public function testRefusedRolesCannotDeactivate(string $role, bool $readOnly): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->signInWith($role, $readOnly);
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/deactivate');

        $this->assertResponseCode(403);
        $this->assertTrue((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    public function testDeactivateOfAForeignUnitIs404(): void
    {
        $foreign = $this->seedUnit($this->devB, null, 'B-1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $foreign . '/deactivate');
        $viaOwnPath = $this->outcome();
        $this->post($this->base($this->devA) . '/' . ($foreign + 1000) . '/deactivate');
        $missing = $this->outcome();

        $this->assertSame(404, $viaOwnPath[0]);
        $this->assertSame($missing, $viaOwnPath);
        $this->assertTrue((bool)$this->unitRow($this->devB, 'B-1')['is_active']);
    }

    private function seedInactiveUnit(int $development, string $number = 'A-1'): int
    {
        $id = $this->seedUnit($development, null, $number);
        $this->db()->execute('UPDATE units SET is_active = false WHERE id = ?', [$id]);

        return $id;
    }

    #[DataProvider('writeRefused')]
    public function testRefusedRolesCannotReactivate(string $role, bool $readOnly): void
    {
        $unit = $this->seedInactiveUnit($this->devA);
        $this->signInWith($role, $readOnly);
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/activate');

        $this->assertResponseCode(403);
        $this->assertFalse((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    public function testReactivateInAnArchivedDevelopmentIsRefused(): void
    {
        $unit = $this->seedInactiveUnit($this->devA);
        $this->archive($this->devA);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/activate');

        $this->assertResponseCode(403);
        $this->assertFalse((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    public function testReactivateOfAForeignUnitIs404LikeAMissingOne(): void
    {
        $foreign = $this->seedInactiveUnit($this->devB, 'B-1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $foreign . '/activate');
        $viaOwnPath = $this->outcome();
        $this->post($this->base($this->devB) . '/' . $foreign . '/activate');
        $viaForeignPath = $this->_response->getStatusCode();
        $this->post($this->base($this->devA) . '/' . ($foreign + 1000) . '/activate');

        $this->assertSame(404, $viaOwnPath[0]);
        $this->assertSame($this->outcome(), $viaOwnPath);
        $this->assertSame(404, $viaForeignPath);
        $this->assertFalse((bool)$this->unitRow($this->devB, 'B-1')['is_active']);
    }

    public function testReactivateNeedsTheCsrfToken(): void
    {
        $unit = $this->seedInactiveUnit($this->devA);
        $this->signInWith('administrator');
        $this->_csrfToken = false;

        $this->post($this->base($this->devA) . '/' . $unit . '/activate');

        $this->assertResponseCode(403);
        $this->assertFalse((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    public function testReactivateIsPostOnly(): void
    {
        $unit = $this->seedInactiveUnit($this->devA);
        $this->signInWith('administrator');

        $this->get($this->base($this->devA) . '/' . $unit . '/activate');

        $this->assertResponseCode(404);
        $this->assertFalse((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    public function testDeactivateInAnArchivedDevelopmentIsRefused(): void
    {
        $unit = $this->seedUnit($this->devA, null, 'A-1');
        $this->archive($this->devA);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/' . $unit . '/deactivate');

        $this->assertResponseCode(403);
        $this->assertTrue((bool)$this->unitRow($this->devA, 'A-1')['is_active']);
    }

    // --- sections ----------------------------------------------------------------------------------------------

    public function testAdministratorCreatesASectionInline(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/sections', ['name' => '  Torre 1  ']);

        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('Sección creada.');
        $row = $this->db()->execute('SELECT development_id, name FROM sections')->fetch('assoc');
        $this->assertSame(['development_id' => $this->devA, 'name' => 'Torre 1'], $row);

        $this->get($this->base($this->devA) . '/add');
        $this->assertResponseContains('Torre 1');
    }

    public function testASectionNameIsUniquePerDevelopmentButNotAcrossThem(): void
    {
        $this->seedSection($this->devA, 'Torre 1');
        $this->seedSection($this->devB, 'Torre 2');
        $this->signInWith('data_entry');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 1']);
        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('No se pudo guardar la sección: ya existe una con ese nombre.');
        $this->assertSame(1, $this->sectionCount($this->devA));

        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 2']);
        $this->assertFlashMessage('Sección creada.');
        $this->assertSame(2, $this->sectionCount($this->devA));
    }

    public function testASectionNameTakenOnlyInTheDatabaseIsAFieldError(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $done = false;
        $this->useSaveThat(function () use (&$done): void {
            if (!$done) {
                $done = true;
                $this->seedSection($this->devA, 'Torre 1');
            }
        });

        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 1']);

        $this->assertTrue($done);
        $this->assertRedirect($this->base($this->devA));
        $this->assertFlashMessage('No se pudo guardar la sección: ya existe una con ese nombre.');
        $this->assertSame(1, $this->sectionCount());
    }

    public function testABlankSectionNameIsRefused(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/sections', ['name' => '   ']);

        $this->assertFlashMessage('No se pudo guardar la sección: escribe un nombre.');
        $this->assertSame(0, $this->sectionCount());
    }

    #[DataProvider('writeRefused')]
    public function testRefusedRolesCannotCreateSections(string $role, bool $readOnly): void
    {
        $this->signInWith($role, $readOnly);
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 1']);

        $this->assertResponseCode(403);
        $this->assertSame(0, $this->sectionCount());
    }

    public function testSectionOfAForeignDevelopmentIs404AndBodyDevelopmentIdIsIgnored(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devB) . '/sections', ['name' => 'Torre 1']);
        $foreign = $this->outcome();
        $this->post($this->base(999999999) . '/sections', ['name' => 'Torre 1']);
        $missing = $this->outcome();
        $this->assertSame(404, $foreign[0]);
        $this->assertSame($missing, $foreign);
        $this->assertSame(0, $this->sectionCount());

        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 1', 'development_id' => (string)$this->devB]);
        $this->assertFlashMessage('Sección creada.');
        $this->assertSame(1, $this->sectionCount($this->devA));
        $this->assertSame(0, $this->sectionCount($this->devB));
    }

    public function testSectionInAnArchivedDevelopmentIsRefused(): void
    {
        $this->archive($this->devA);
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 1']);

        $this->assertResponseCode(403);
        $this->assertSame(0, $this->sectionCount());
    }

    public function testSectionCreatedWhileTheDevelopmentIsArchivedIsShownAsArchived(): void
    {
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $archived = false;
        $this->useSaveThat(function () use (&$archived): void {
            if (!$archived) {
                $archived = true;
                $this->archive($this->devA);
            }
        });

        $this->post($this->base($this->devA) . '/sections', ['name' => 'Torre 1']);

        $this->assertTrue($archived);
        $this->assertFlashMessage('El desarrollo está archivado: no se pueden guardar cambios.');
        $this->assertSame(0, $this->sectionCount());
    }

    // --- no delete ---------------------------------------------------------------------------------------------

    public function testThereIsNoDeleteRouteForUnitsOrSections(): void
    {
        $section = $this->seedSection($this->devA, 'Torre 1');
        $unit = $this->seedUnit($this->devA, $section, '1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();

        foreach (
            [
                $this->base($this->devA) . '/' . $unit . '/delete',
                $this->base($this->devA) . '/' . $unit,
                $this->base($this->devA) . '/sections/' . $section . '/delete',
                '/developments/' . $this->devA . '/sections/' . $section,
            ] as $url
        ) {
            $this->post($url);
            $this->assertResponseCode(404, $url);
            $this->delete($url);
            $this->assertResponseCode(404, $url);
        }

        $this->assertSame(1, $this->unitCount());
        $this->assertSame(1, $this->sectionCount());
    }

    public function testASectionDeletedDuringTheSaveIsAFieldErrorOnTheSection(): void
    {
        $section = $this->seedSection($this->devA, 'Torre 1');
        $this->signInWith('administrator');
        $this->enableCsrfToken();
        $done = false;
        $this->useSaveThat(function () use (&$done, $section): void {
            if (!$done) {
                $done = true;
                $this->db()->execute('DELETE FROM sections WHERE id = ?', [$section]);
            }
        });

        $this->post($this->base($this->devA) . '/add', $this->payload(['section_id' => (string)$section]));

        $this->assertTrue($done);
        $this->assertResponseOk();
        $this->assertResponseContains('El valor de «torre» no existe en este desarrollo.');
        $this->assertSame(0, $this->unitCount());
    }

    public function testTheFormLeavesValidationToTheServerAndNeverBlocksAValidMaximum(): void
    {
        $this->signInWith('administrator');

        $this->get($this->base($this->devA) . '/add');

        $this->assertResponseOk();
        // No browser-side message in another language, and room for 9999999999.99 and 100.0000.
        $this->assertResponseNotContains('data-validity-message');
        $this->assertResponseContains('name="lot_area_m2"');
        $this->assertResponseNotContains('maxlength="12"');
        $this->assertResponseNotContains('maxlength="7"');
    }

    private function useSaveThat(callable $behaviour): void
    {
        $listener = function (EventInterface $event) use ($behaviour): void {
            $behaviour();
        };
        $this->saveListeners[] = $listener;
        EventManager::instance()->on('Model.beforeSave', ['priority' => 1], $listener);
    }
}
