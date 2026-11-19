<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\DevelopmentScope;
use App\Model\Entity\Section;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Model\Exception\MissingDevelopmentScopeException;
use App\Model\Table\SectionsTable;
use App\Test\Support\DatabaseTestCase;
use Closure;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Sections of a development under layer 1: scoped reads and writes, bulk statements refused, validation that mirrors
 * the database checks (`name ~ '\S'`, at most 150 characters) and a name unique per development.
 *
 * The session is a super-administrator's, so row-level security hides nothing and every filtered or refused row is
 * the application layer's doing.
 */
class SectionsTableTest extends DatabaseTestCase
{
    private SectionsTable $sections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->clear();
        $this->sections = $this->getTableLocator()->get('Sections');
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
     * @param int $development Development the section is saved in.
     * @return array<string, mixed> Save options.
     */
    private function saveIn(int $development): array
    {
        return ['developmentScope' => DevelopmentScope::only([$development]), 'developmentId' => $development];
    }

    public function testAFindWithoutAScopeThrows(): void
    {
        $this->seedSection($this->createDevelopment('Norte'));
        $this->actAsSuperAdministrator();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->sections->find()->all();
    }

    public function testAScopeNarrowsTheListToItsDevelopments(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $this->seedSection($north, 'Torre N');
        $this->seedSection($south, 'Torre S');
        $this->actAsSuperAdministrator();

        $names = $this->sections->find('all', developmentScope: DevelopmentScope::only([$north]))
            ->all()->extract('name')->toList();

        $this->assertSame(['Torre N'], $names);
    }

    /**
     * @return array<string, array{\Closure(\App\Model\Table\SectionsTable): mixed}>
     */
    public static function bulkStatements(): array
    {
        return [
            'updateAll' => [fn(SectionsTable $table) => $table->updateAll(['name' => 'x'], ['id >' => 0])],
            'deleteAll' => [fn(SectionsTable $table) => $table->deleteAll(['id >' => 0])],
        ];
    }

    #[DataProvider('bulkStatements')]
    public function testTheTableRefusesBulkStatements(Closure $statement): void
    {
        $this->seedSection($this->createDevelopment('Norte'));
        $this->actAsSuperAdministrator();

        $this->expectException(LogicException::class);

        $statement($this->sections);
    }

    public function testASectionTakesItsDevelopmentFromTheSaveOption(): void
    {
        $north = $this->createDevelopment('Norte');
        $this->actAsSuperAdministrator();

        $saved = $this->sections->save($this->sections->newEntity(['name' => 'Torre A']), $this->saveIn($north));
        $this->commitSession();

        $this->assertInstanceOf(Section::class, $saved);
        $stored = $this->db()->execute('SELECT development_id, name FROM sections')->fetch('assoc');
        $this->assertSame(['development_id' => $north, 'name' => 'Torre A'], $stored);
    }

    public function testTheDevelopmentIsNotMassAssignable(): void
    {
        $section = $this->sections->newEntity(['name' => 'Torre A', 'development_id' => 5, 'id' => 9, 'created' => '2000-01-01']);

        $this->assertNull($section->get('development_id'));
        $this->assertNull($section->get('id'));
        $this->assertNull($section->get('created'));
        $this->assertSame('Torre A', $section->get('name'));
    }

    public function testABodyNamingAnotherDevelopmentThrows(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');

        $this->expectException(DevelopmentScopeViolation::class);

        $this->sections->newEntity(['name' => 'Torre A', 'development_id' => $south], ['developmentId' => $north]);
    }

    public function testASaveOutsideTheScopeThrows(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $this->actAsSuperAdministrator();

        $this->expectException(DevelopmentScopeViolation::class);

        $this->sections->save(
            $this->sections->newEntity(['name' => 'Torre A']),
            ['developmentScope' => DevelopmentScope::only([$north]), 'developmentId' => $south],
        );
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function names(): array
    {
        return [
            'plain' => ['Torre A', true],
            'accented' => ['Privada Águila', true],
            'at the limit' => [str_repeat('a', 150), true], 'multibyte at the limit' => [str_repeat('á', 150), true],
            'multibyte over the limit' => [str_repeat('á', 151), false],
            'over the limit' => [str_repeat('a', 151), false],
            'empty' => ['', false],
            'blank' => ['   ', false],
            'tab and line break' => ["\t\n", false],
            'null' => [null, false],
            'array' => [['x'], false],
        ];
    }

    #[DataProvider('names')]
    public function testTheNameMustHaveContentAndAtMost150Characters(mixed $name, bool $valid): void
    {
        $section = $this->sections->newEntity(['name' => $name]);

        $this->assertSame($valid ? [] : ['name'], array_keys($section->getErrors()));
    }

    public function testTheNameIsRequiredOnCreate(): void
    {
        $this->assertSame(['name'], array_keys($this->sections->newEntity([])->getErrors()));
    }

    public function testTheNameIsTrimmed(): void
    {
        $this->assertSame('Torre A', $this->sections->newEntity(['name' => "  Torre A \t"])->get('name'));
    }

    public function testANameAlreadyUsedInTheDevelopmentIsRejectedWithAFieldError(): void
    {
        $north = $this->createDevelopment('Norte');
        $this->seedSection($north, 'Torre A');
        $this->actAsSuperAdministrator();

        $duplicate = $this->sections->newEntity(['name' => 'Torre A']);

        $this->assertFalse($this->sections->save($duplicate, $this->saveIn($north)));
        $this->assertArrayHasKey('name', $duplicate->getErrors());
    }

    public function testTheSameNameInAnotherDevelopmentIsAccepted(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $this->seedSection($north, 'Torre A');
        $this->actAsSuperAdministrator();

        $saved = $this->sections->save($this->sections->newEntity(['name' => 'Torre A']), $this->saveIn($south));

        $this->assertInstanceOf(Section::class, $saved);
    }

    public function testRenamingToAnExistingNameIsRejectedButKeepingTheOwnNameIsNot(): void
    {
        $north = $this->createDevelopment('Norte');
        $a = $this->seedSection($north, 'Torre A');
        $this->seedSection($north, 'Torre B');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north]);
        /** @var \App\Model\Entity\Section $section */
        $section = $this->sections->get($a, developmentScope: $scope);

        $same = $this->sections->patchEntity($section, ['name' => 'Torre A']);
        $this->assertNotFalse($this->sections->save($same, ['developmentScope' => $scope]));

        $clash = $this->sections->patchEntity($section, ['name' => 'Torre B']);
        $this->assertFalse($this->sections->save($clash, ['developmentScope' => $scope]));
        $this->assertArrayHasKey('name', $clash->getErrors());
    }

    public function testTheDevelopmentCannotBeChangedOnAnExistingSection(): void
    {
        $north = $this->createDevelopment('Norte');
        $south = $this->createDevelopment('Sur');
        $id = $this->seedSection($north, 'Torre A');
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$north, $south]);
        /** @var \App\Model\Entity\Section $section */
        $section = $this->sections->get($id, developmentScope: $scope);
        $section->set('development_id', $south);

        $this->expectException(DevelopmentScopeViolation::class);

        $this->sections->save($section, ['developmentScope' => $scope]);
    }
}
