<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\DevelopmentScope;
use App\Model\Entity\Unit;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Model\Exception\MissingDevelopmentScopeException;
use App\Model\Table\UnitsTable;
use App\Test\Support\DatabaseTestCase;
use Closure;
use LogicException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * Units under layer 1: scoped reads and writes, bulk statements refused, validation that mirrors the database checks,
 * and the two rules (`IsUnique`, `ExistsIn` on the section) that run under the save scope (design AD4b, W-B).
 *
 * The session is a super-administrator's, so row-level security hides nothing and every filtered or refused row is
 * the application layer's doing.
 */
class UnitsTableTest extends DatabaseTestCase
{
    private UnitsTable $units;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->clear();
        $this->units = $this->getTableLocator()->get('Units');
    }

    protected function tearDown(): void
    {
        $this->getTableLocator()->clear();
        parent::tearDown();
    }

    private function actAsSuperAdministrator(): void
    {
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);
    }

    /**
     * @param int $development Development the unit is saved in.
     * @return array<string, mixed> Save options.
     */
    private function saveIn(int $development): array
    {
        return ['developmentScope' => DevelopmentScope::only([$development]), 'developmentId' => $development];
    }

    /**
     * @param array<string, mixed> $override Fields to replace or add.
     * @return array<string, mixed> A valid payload.
     */
    private static function payload(array $override = []): array
    {
        return $override + ['number' => '101', 'type' => 'apartment'];
    }

    public function testAFindWithoutAScopeThrows(): void
    {
        $this->seedUnit($this->createDevelopment('Norte'), null);
        $this->actAsSuperAdministrator();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->units->find()->all();
    }

    public function testAScopeNarrowsTheListToItsDevelopments(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $this->seedUnit($north, null, 'N1');
        $this->seedUnit($south, null, 'S1');
        $this->actAsSuperAdministrator();

        $numbers = $this->units->find('all', developmentScope: DevelopmentScope::only([$north]))
            ->all()->extract('number')->toList();

        $this->assertSame(['N1'], $numbers);
    }

    /**
     * @return array<string, array{\Closure(\App\Model\Table\UnitsTable): mixed}>
     */
    public static function bulkStatements(): array
    {
        return [
            'updateAll' => [fn(UnitsTable $table) => $table->updateAll(['number' => 'x'], ['id >' => 0])],
            'deleteAll' => [fn(UnitsTable $table) => $table->deleteAll(['id >' => 0])],
        ];
    }

    #[DataProvider('bulkStatements')]
    public function testTheTableRefusesBulkStatements(Closure $statement): void
    {
        $this->seedUnit($this->createDevelopment('Norte'), null);
        $this->actAsSuperAdministrator();

        $this->expectException(LogicException::class);

        $statement($this->units);
    }

    public function testAUnitIsSavedWithItsDevelopmentSectionAndMeasurements(): void
    {
        $north = $this->createDevelopment('Norte');
        $section = $this->seedSection($north, 'Torre A');
        $this->actAsSuperAdministrator();
        $unit = $this->units->newEntity(self::payload([
            'section_id' => $section, 'number' => ' 12B ', 'type' => 'house', 'lot_area_m2' => '120.50',
            'indiviso_pct' => '1.2500',
        ]));

        $saved = $this->units->save($unit, $this->saveIn($north));
        $this->commitSession();

        $this->assertInstanceOf(Unit::class, $saved);
        $stored = $this->db()->execute(
            'SELECT development_id, section_id, number, type, lot_area_m2::text AS area, indiviso_pct::text AS pct,
                    is_active FROM units',
        )->fetch('assoc');
        $this->assertSame([
            'development_id' => $north, 'section_id' => $section, 'number' => '12B', 'type' => 'house',
            'area' => '120.50', 'pct' => '1.2500', 'is_active' => true,
        ], $stored);
    }

    public function testAUnitWithNoSectionAndNoMeasurementsIsActiveByDefault(): void
    {
        $north = $this->createDevelopment('Norte');
        $this->actAsSuperAdministrator();

        $saved = $this->units->save($this->units->newEntity(self::payload()), $this->saveIn($north));
        $this->commitSession();

        $this->assertInstanceOf(Unit::class, $saved);
        $stored = $this->db()->execute('SELECT section_id, lot_area_m2, indiviso_pct, is_active FROM units')->fetch('assoc');
        $this->assertSame(['section_id' => null, 'lot_area_m2' => null, 'indiviso_pct' => null, 'is_active' => true], $stored);
    }

    public function testTheDevelopmentAndTheKeyAreNotMassAssignable(): void
    {
        $unit = $this->units->newEntity(self::payload(['development_id' => 5, 'id' => 9, 'created' => '2000-01-01']));

        $this->assertNull($unit->get('development_id'));
        $this->assertNull($unit->get('id'));
        $this->assertNull($unit->get('created'));
    }

    public function testABodyNamingAnotherDevelopmentThrows(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');

        $this->expectException(DevelopmentScopeViolation::class);

        $this->units->newEntity(self::payload(['development_id' => $south]), ['developmentId' => $north]);
    }

    public function testASaveOutsideTheScopeThrows(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $this->actAsSuperAdministrator();

        $this->expectException(DevelopmentScopeViolation::class);

        $this->units->save(
            $this->units->newEntity(self::payload()),
            ['developmentScope' => DevelopmentScope::only([$north]), 'developmentId' => $south],
        );
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function numbers(): array
    {
        return [
            'plain' => ['101', true],
            'letters' => ['Lote 7-B', true],
            'at the limit' => [str_repeat('9', 50), true], 'multibyte at the limit' => [str_repeat('á', 50), true],
            'multibyte over the limit' => [str_repeat('á', 51), false],
            'over the limit' => [str_repeat('9', 51), false],
            'empty' => ['', false],
            'blank' => ['   ', false],
            'tab and line break' => ["\t\n", false],
            'null' => [null, false],
            'array' => [['1'], false],
        ];
    }

    #[DataProvider('numbers')]
    public function testTheNumberMustHaveContentAndAtMost50Characters(mixed $number, bool $valid): void
    {
        $unit = $this->units->newEntity(self::payload(['number' => $number]));

        $this->assertSame($valid ? [] : ['number'], array_keys($unit->getErrors()));
    }

    public function testNumberAndTypeAreRequiredOnCreate(): void
    {
        $this->assertSame(['number', 'type'], array_keys($this->units->newEntity([])->getErrors()));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function types(): array
    {
        return [
            'apartment' => ['apartment', true], 'house' => ['house', true], 'lot' => ['lot', true],
            'unknown' => ['villa', false], 'case differs' => ['House', false], 'empty' => ['', false],
            'null' => [null, false], 'array' => [['house'], false],
        ];
    }

    #[DataProvider('types')]
    public function testTheTypeMustBeApartmentHouseOrLot(mixed $type, bool $valid): void
    {
        $unit = $this->units->newEntity(self::payload(['type' => $type]));

        $this->assertSame($valid ? [] : ['type'], array_keys($unit->getErrors()));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function areas(): array
    {
        return [
            'omitted as null' => [null, true], 'empty string' => ['', true], 'positive' => ['120.5', true],
            'small' => ['0.01', true], 'rounds up to 0.01' => ['0.005', true], 'rounds down to zero' => ['0.004', false], 'float' => [80.25, true], 'largest column value' => ['9999999999.99', true],
            'zero' => ['0', false], 'negative' => ['-1', false], 'NaN' => ['NaN', false], 'text' => ['big', false],
            'infinity' => ['Infinity', false], 'too many digits' => ['10000000000', false], 'rounds past the column' => ['9999999999.995', false], 'array' => [['1'], false],
        ];
    }

    #[DataProvider('areas')]
    public function testTheLotAreaIsOptionalButPositiveAndWithinTheColumn(mixed $area, bool $valid): void
    {
        $unit = $this->units->newEntity(self::payload(['lot_area_m2' => $area]));

        $this->assertSame($valid ? [] : ['lot_area_m2'], array_keys($unit->getErrors()));
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function percentages(): array
    {
        return [
            'omitted as null' => [null, true], 'empty string' => ['', true], 'small' => ['0.0001', true], 'rounds to zero' => ['0.00004', false],
            'typical' => ['2.5', true], 'whole' => ['100', true], 'float' => [12.5, true],
            'zero' => ['0', false], 'negative' => ['-5', false], 'above 100' => ['100.0001', false],
            'one twenty' => ['120', false], 'rounds to 100' => ['100.00004', false], 'NaN' => ['NaN', false], 'text' => ['half', false], 'array' => [['1'], false],
        ];
    }

    #[DataProvider('percentages')]
    public function testTheIndivisoIsOptionalButPositiveAndAtMost100(mixed $percentage, bool $valid): void
    {
        $unit = $this->units->newEntity(self::payload(['indiviso_pct' => $percentage]));

        $this->assertSame($valid ? [] : ['indiviso_pct'], array_keys($unit->getErrors()));
    }

    public function testAnEmptyMeasurementIsStoredAsNull(): void
    {
        $unit = $this->units->newEntity(self::payload(['lot_area_m2' => '', 'indiviso_pct' => '  ', 'section_id' => '']));

        $this->assertNull($unit->get('lot_area_m2'));
        $this->assertNull($unit->get('indiviso_pct'));
        $this->assertNull($unit->get('section_id'));
    }

    public function testTheNumberIsTrimmed(): void
    {
        $this->assertSame('101', $this->units->newEntity(self::payload(['number' => " 101\t"]))->get('number'));
    }

    public function testTheActiveFlagMustBeABoolean(): void
    {
        $this->assertSame([], $this->units->newEntity(self::payload(['is_active' => false]))->getErrors());
        $this->assertSame(['is_active'], array_keys($this->units->newEntity(self::payload(['is_active' => 'maybe']))->getErrors()));
    }

    public function testANumberAlreadyUsedInTheSameSectionIsRejectedWithAFieldError(): void
    {
        $north = $this->createDevelopment('Norte');
        $section = $this->seedSection($north);
        $this->seedUnit($north, $section, '101');
        $this->actAsSuperAdministrator();

        $duplicate = $this->units->newEntity(self::payload(['section_id' => $section]));

        $this->assertFalse($this->units->save($duplicate, $this->saveIn($north)));
        $this->assertArrayHasKey('number', $duplicate->getErrors());
    }

    public function testUnitsWithNoSectionShareOneNamespacePerDevelopment(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();

        $duplicate = $this->units->newEntity(self::payload());
        $this->assertFalse($this->units->save($duplicate, $this->saveIn($north)));
        $this->assertArrayHasKey('number', $duplicate->getErrors());

        $otherDevelopment = $this->units->save($this->units->newEntity(self::payload()), $this->saveIn($south));
        $this->assertInstanceOf(Unit::class, $otherDevelopment);
    }

    public function testTheSameNumberInAnotherSectionOrOutsideAnySectionIsAccepted(): void
    {
        $north = $this->createDevelopment('Norte');
        $a = $this->seedSection($north, 'Torre A');
        $b = $this->seedSection($north, 'Torre B');
        $this->seedUnit($north, $a, '101');
        $this->actAsSuperAdministrator();

        $inB = $this->units->save($this->units->newEntity(self::payload(['section_id' => $b])), $this->saveIn($north));
        $none = $this->units->save($this->units->newEntity(self::payload()), $this->saveIn($north));

        $this->assertInstanceOf(Unit::class, $inB);
        $this->assertInstanceOf(Unit::class, $none);
    }

    public function testAnUnknownSectionIsRejectedWithAFieldError(): void
    {
        $north = $this->createDevelopment('Norte');
        $this->actAsSuperAdministrator();

        $unit = $this->units->newEntity(self::payload(['section_id' => 999999]));

        $this->assertFalse($this->units->save($unit, $this->saveIn($north)));
        $this->assertArrayHasKey('section_id', $unit->getErrors());
    }

    public function testASectionOfAnotherDevelopmentIsRejectedEvenWhenTheScopeReachesIt(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $foreign = $this->seedSection($south, 'Torre S');
        $this->actAsSuperAdministrator();

        $unit = $this->units->newEntity(self::payload(['section_id' => $foreign]));
        $saved = $this->units->save($unit, [
            'developmentScope' => DevelopmentScope::only([$north, $south]), 'developmentId' => $north,
        ]);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('section_id', $unit->getErrors());
    }

    public function testTheSectionRuleRunsUnderTheSaveScope(): void
    {
        $north = $this->createDevelopment('Norte');
        $section = $this->seedSection($north);
        $this->actAsSuperAdministrator();

        $saved = $this->units->save(
            $this->units->newEntity(self::payload(['section_id' => $section])),
            $this->saveIn($north),
        );

        $this->assertInstanceOf(Unit::class, $saved);
    }

    public function testASectionCanBeAddedAndRemovedFromAnExistingUnit(): void
    {
        $north = $this->createDevelopment('Norte');
        $section = $this->seedSection($north);
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north]);
        /** @var \App\Model\Entity\Unit $unit */
        $unit = $this->units->get($id, developmentScope: $scope);

        $this->assertNotFalse($this->units->save($this->units->patchEntity($unit, ['section_id' => $section]), ['developmentScope' => $scope]));
        $this->assertNotFalse($this->units->save($this->units->patchEntity($unit, ['section_id' => '']), ['developmentScope' => $scope]));
    }

    public function testRenumberingToATakenNumberIsRejectedButKeepingTheOwnNumberIsNot(): void
    {
        $north = $this->createDevelopment('Norte');
        $first = $this->seedUnit($north, null, '101');
        $this->seedUnit($north, null, '102');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north]);
        /** @var \App\Model\Entity\Unit $unit */
        $unit = $this->units->get($first, developmentScope: $scope);

        $this->assertNotFalse($this->units->save($this->units->patchEntity($unit, ['number' => '101', 'type' => 'house']), ['developmentScope' => $scope]));

        $clash = $this->units->patchEntity($unit, ['number' => '102']);
        $this->assertFalse($this->units->save($clash, ['developmentScope' => $scope]));
        $this->assertArrayHasKey('number', $clash->getErrors());
    }

    public function testTheActiveFlagIsNeverAssignedFromRequestData(): void
    {
        $north = $this->createDevelopment('Norte');
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north]);

        $created = $this->units->newEntity(self::payload(['is_active' => false]));
        $this->assertNull($created->get('is_active'));

        $unit = $this->units->get($id, developmentScope: $scope);
        $this->units->patchEntity($unit, ['is_active' => false, 'number' => '102']);
        $this->assertTrue($unit->is_active);
        $this->assertSame('102', $unit->number);
    }

    public function testThePayerIsNeverAssignedFromRequestDataAndTheTableNeverWritesIt(): void
    {
        $north = $this->createDevelopment('Norte');
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north]);

        $created = $this->units->newEntity(self::payload(['payer' => 'tenant', 'number' => '900']));
        $this->assertNull($created->get('payer'));
        $this->assertNotFalse($this->units->save($created, $this->saveIn($north)));

        // An edit of a unit read from the database (its `payer` is loaded) leaves the payer alone.
        $unit = $this->units->get($id, developmentScope: $scope);
        $this->units->patchEntity($unit, ['payer' => 'tenant', 'number' => '102']);
        $this->assertSame('owner', $unit->get('payer'));
        $this->assertNotFalse($this->units->save($unit, ['developmentScope' => $scope]));
        $this->commitSession();

        $this->assertSame(
            [['owner'], ['owner']],
            $this->db()->execute('SELECT payer FROM units ORDER BY id')->fetchAll(),
        );
    }

    public function testAPayerSetByHandIsRefusedByTheDatabaseEvenForTheSuperAdministrator(): void
    {
        $north = $this->createDevelopment('Norte');
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north]);
        $unit = $this->units->get($id, developmentScope: $scope);
        $unit->set('payer', 'tenant');

        $state = null;
        try {
            $this->units->save($unit, ['developmentScope' => $scope]);
        } catch (Throwable $e) {
            for (; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                    $state = (string)$e->errorInfo[0];
                    break;
                }
            }
        }

        // 42501: the runtime role has no UPDATE privilege on the payer column (S-2); only unit_set_payer changes it.
        $this->assertSame('42501', $state);
        $this->endSession();
        $this->assertSame('owner', $this->db()->execute('SELECT payer FROM units WHERE id = ?', [$id])->fetch()[0]);
    }

    public function testAUnitCanBeDeactivatedAndReactivatedWithoutLosingTheRow(): void
    {
        $north = $this->createDevelopment('Norte');
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north]);
        /** @var \App\Model\Entity\Unit $unit */
        $unit = $this->units->get($id, developmentScope: $scope);

        $this->assertNotFalse($this->units->save($this->units->patchEntity($unit->set('is_active', false), []), ['developmentScope' => $scope]));
        $this->assertFalse($this->units->get($id, developmentScope: $scope)->get('is_active'));
        $this->assertNotFalse($this->units->save($this->units->patchEntity($unit->set('is_active', true), []), ['developmentScope' => $scope]));
        $this->assertTrue($this->units->get($id, developmentScope: $scope)->get('is_active'));
    }

    public function testTheDevelopmentCannotBeChangedOnAnExistingUnit(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north, $south]);
        /** @var \App\Model\Entity\Unit $unit */
        $unit = $this->units->get($id, developmentScope: $scope);
        $unit->set('development_id', $south);

        $this->expectException(DevelopmentScopeViolation::class);

        $this->units->save($unit, ['developmentScope' => $scope]);
    }

    public function testASectionIsContainedUnderTheScopeOfItsUnits(): void
    {
        $north = $this->createDevelopment('Norte');
        $section = $this->seedSection($north, 'Torre A');
        $this->seedUnit($north, $section, '101');
        $this->seedUnit($north, null, '102');
        $this->actAsSuperAdministrator();

        $units = $this->units->find('all', developmentScope: DevelopmentScope::only([$north]))
            ->contain(['Sections'])->orderBy(['Units.number' => 'ASC'])->all();

        $this->assertSame('Torre A', $units->first()->section->name);
        $this->assertNull($units->last()->section);
    }

    /**
     * @return array<string, array{string, string, string}> Column, input, stored value as text.
     */
    public static function acceptedExtremes(): array
    {
        return [
            'area rounds up to 0.01' => ['lot_area_m2', '0.005', '0.01'],
            'area largest' => ['lot_area_m2', '9999999999.99', '9999999999.99'],
            'indiviso smallest stored' => ['indiviso_pct', '0.0001', '0.0001'],
            'indiviso rounds up' => ['indiviso_pct', '0.00005', '0.0001'],
            'indiviso 100' => ['indiviso_pct', '100', '100.0000'],
        ];
    }

    #[DataProvider('acceptedExtremes')]
    public function testTheAcceptedExtremesAreStoredByTheRealDatabase(string $field, string $input, string $stored): void
    {
        $north = $this->createDevelopment('Norte');
        $this->actAsSuperAdministrator();

        $saved = $this->units->save($this->units->newEntity(self::payload([$field => $input])), $this->saveIn($north));
        $this->commitSession();

        $this->assertInstanceOf(Unit::class, $saved);
        $this->assertSame($stored, $this->db()->execute("SELECT {$field}::text FROM units")->fetch()[0]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function rejectedExtremes(): array
    {
        return [
            'area past the column after rounding' => ['lot_area_m2', '9999999999.995'],
            'area rounding to zero' => ['lot_area_m2', '0.004'],
            'indiviso past 100 after rounding' => ['indiviso_pct', '100.00004'],
            'indiviso rounding to zero' => ['indiviso_pct', '0.00004'],
        ];
    }

    #[DataProvider('rejectedExtremes')]
    public function testTheRejectedExtremesAreFieldErrorsAndNeverReachTheDatabase(string $field, string $input): void
    {
        $north = $this->createDevelopment('Norte');
        $this->actAsSuperAdministrator();
        $unit = $this->units->newEntity(self::payload([$field => $input]));

        $this->assertFalse($this->units->save($unit, $this->saveIn($north)));
        $this->assertSame([$field], array_keys($unit->getErrors()));
    }

    public function testAMatchingDevelopmentInTheBodyIsAcceptedAndNeverAssigned(): void
    {
        $north = $this->createDevelopment('Norte');

        $unit = $this->units->newEntity(self::payload(['development_id' => $north]), ['developmentId' => $north]);

        $this->assertSame([], $unit->getErrors());
        $this->assertNull($unit->get('development_id'));
    }

    public function testPatchingWithADevelopmentInTheBodyIsCheckedAgainstTheTarget(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north, $south]);
        /** @var \App\Model\Entity\Unit $unit */
        $unit = $this->units->get($id, developmentScope: $scope);
        $options = ['developmentId' => $north, 'developmentScope' => $scope];

        $patched = $this->units->patchEntity($unit, ['number' => '102', 'development_id' => $north], $options);
        $this->assertSame('102', $patched->number);
        $this->assertSame($north, $patched->development_id);
        $this->assertFalse($patched->isDirty('development_id'));

        $this->expectException(DevelopmentScopeViolation::class);

        $this->units->patchEntity($unit, ['development_id' => $south], $options);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function badSectionIds(): array
    {
        return ['text' => ['abc'], 'array' => [['1']], 'negative' => [-1], 'zero' => [0], 'decimal' => ['1.5']];
    }

    #[DataProvider('badSectionIds')]
    public function testAMalformedSectionIdIsAFieldError(mixed $sectionId): void
    {
        $unit = $this->units->newEntity(self::payload(['section_id' => $sectionId]));

        $this->assertSame(['section_id'], array_keys($unit->getErrors()));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function badActiveFlags(): array
    {
        return ['null' => [null], 'empty' => ['']];
    }

    #[DataProvider('badActiveFlags')]
    public function testTheActiveFlagCannotBeNullOrEmpty(mixed $flag): void
    {
        $unit = $this->units->newEntity(self::payload(['is_active' => $flag]));

        $this->assertSame(['is_active'], array_keys($unit->getErrors()));
    }

    public function testMovingAUnitToASectionOfAnotherDevelopmentIsRejected(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $foreign = $this->seedSection($south, 'Torre S');
        $id = $this->seedUnit($north, null, '101');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north, $south]);
        /** @var \App\Model\Entity\Unit $unit */
        $unit = $this->units->get($id, developmentScope: $scope);
        $moved = $this->units->patchEntity($unit, ['section_id' => $foreign]);

        $this->assertFalse($this->units->save($moved, ['developmentScope' => $scope]));
        $this->assertArrayHasKey('section_id', $moved->getErrors());
    }
}
