<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Section;
use App\Model\Entity\Unit;
use App\Policy\SectionPolicy;
use App\Policy\SectionsTablePolicy;
use App\Policy\UnitPolicy;
use App\Policy\UnitsTablePolicy;
use App\Test\Support\DatabaseTestCase;
use Authentication\Identity;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use Authorization\Policy\OrmResolver;
use Cake\Datasource\EntityInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Who may see and change units and sections (spec units: "Who may read and write"). Reading needs `units.view`;
 * writing needs `units.manage` and a development that is not archived (W-E), the super-administrator included.
 * Nothing allows deletion: units are deactivated, and only the super-administrator deletes rows at the database.
 *
 * The user's session is open while the policy runs, as in a request, so the development is read under row-level
 * security as that user.
 */
class UnitsPoliciesTest extends DatabaseTestCase
{
    private int $development;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->clear();
        $this->development = $this->createDevelopment('Norte');
    }

    protected function tearDown(): void
    {
        $this->getTableLocator()->clear();
        parent::tearDown();
    }

    private function identity(int $userId): IdentityInterface
    {
        return new IdentityDecorator(
            $this->createStub(AuthorizationServiceInterface::class),
            new Identity(['id' => $userId]),
        );
    }

    /**
     * @param string|null $role Role in the development (`super_administrator` is global); null for no assignment.
     * @param bool $readOnly Whether the assignment is read-only.
     * @return \Authorization\IdentityInterface The user, with a request session open.
     */
    private function userWith(?string $role, bool $readOnly = false): IdentityInterface
    {
        $user = $this->createUser('ann');
        // A user may hold several assignments in one development: `resident+data_entry`.
        foreach ($role === null ? [] : explode('+', $role) as $code) {
            $this->assignRole($user, $code, $code === 'super_administrator' ? null : $this->development, readOnly: $readOnly);
        }
        $this->actAs($user);

        return $this->identity($user);
    }

    private function archive(): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->development]);
    }

    /**
     * @return array<string, array{string|null, bool, bool, bool}> Role, read-only, may read, may write.
     */
    public static function actors(): array
    {
        return [
            'super_administrator' => ['super_administrator', false, true, true],
            'administrator' => ['administrator', false, true, true],
            'data_entry' => ['data_entry', false, true, true],
            'read-only administrator' => ['administrator', true, true, false],
            'accountant' => ['accountant', false, true, false],
            'supervisor' => ['supervisor', false, true, false],
            'resident' => ['resident', false, false, false],
            'guard' => ['guard', false, false, false],
            'read-only super_administrator' => ['super_administrator', true, true, false],
            'resident and data_entry' => ['resident+data_entry', false, true, true],
            'resident and accountant' => ['resident+accountant', false, true, false],
            'no assignment' => [null, false, false, false],
        ];
    }

    /**
     * @return array<string, array{class-string, class-string}> Entity policy and the entity it guards.
     */
    public static function entityPolicies(): array
    {
        return [
            'units' => [UnitPolicy::class, Unit::class],
            'sections' => [SectionPolicy::class, Section::class],
        ];
    }

    /**
     * @return array<string, array{class-string, class-string, string|null, bool, bool, bool}>
     */
    public static function entityMatrix(): array
    {
        $cases = [];
        foreach (self::entityPolicies() as $kind => [$policy, $entity]) {
            foreach (self::actors() as $actor => [$role, $readOnly, $read, $write]) {
                $cases["{$kind}: {$actor}"] = [$policy, $entity, $role, $readOnly, $read, $write];
            }
        }

        return $cases;
    }

    private function row(string $entity): EntityInterface
    {
        return new $entity(['id' => 1, 'development_id' => $this->development]);
    }

    #[DataProvider('entityMatrix')]
    public function testAnEntityIsReadWithUnitsViewAndWrittenWithUnitsManage(
        string $policy,
        string $entity,
        ?string $role,
        bool $readOnly,
        bool $mayRead,
        bool $mayWrite,
    ): void {
        $user = $this->userWith($role, $readOnly);
        $guard = new $policy();
        $row = $this->row($entity);

        $this->assertSame($mayRead, $guard->canView($user, $row), 'view');
        $this->assertSame($mayWrite, $guard->canEdit($user, $row), 'edit');
        $this->assertSame($mayWrite, $guard->canAdd($user, $row), 'add');
    }

    /**
     * @return array<string, array{class-string, class-string}>
     */
    public static function entityKinds(): array
    {
        return self::entityPolicies();
    }

    /**
     * @return array<string, array{class-string, class-string, string}>
     */
    public static function archivedCases(): array
    {
        $cases = [];
        foreach (self::entityPolicies() as $kind => [$policy, $entity]) {
            foreach (['super_administrator', 'administrator', 'data_entry'] as $role) {
                $cases["{$kind}: {$role}"] = [$policy, $entity, $role];
            }
        }

        return $cases;
    }

    #[DataProvider('archivedCases')]
    public function testAnArchivedDevelopmentKeepsReadsButRefusesEveryWrite(string $policy, string $entity, string $role): void
    {
        $this->archive();
        $user = $this->userWith($role);
        $guard = new $policy();
        $row = $this->row($entity);

        $this->assertTrue($guard->canView($user, $row));
        $this->assertFalse($guard->canEdit($user, $row));
        $this->assertFalse($guard->canAdd($user, $row));
    }

    #[DataProvider('entityKinds')]
    public function testAnAdministratorOfAnotherDevelopmentIsRefusedEverything(string $policy, string $entity): void
    {
        $other = $this->createDevelopment('Sur');
        $user = $this->createUser('bob');
        $this->assignRole($user, 'administrator', $other);
        $this->actAs($user);
        $identity = $this->identity($user);
        $guard = new $policy();
        $row = $this->row($entity);

        $this->assertFalse($guard->canView($identity, $row));
        $this->assertFalse($guard->canEdit($identity, $row));
        $this->assertFalse($guard->canAdd($identity, $row));
    }

    #[DataProvider('entityKinds')]
    public function testAnEntityWithoutADevelopmentIsRefusedEverything(string $policy, string $entity): void
    {
        $root = $this->userWith('super_administrator');
        $guard = new $policy();

        $this->assertFalse($guard->canView($root, new $entity([])));
        $this->assertFalse($guard->canEdit($root, new $entity([])));
        $this->assertFalse($guard->canAdd($root, new $entity([])));
    }

    #[DataProvider('entityKinds')]
    public function testTheAuthorizationServiceDeniesDeletionToEveryone(string $policy, string $entity): void
    {
        $service = new AuthorizationService(new OrmResolver());
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);
        $identity = new IdentityDecorator($service, new Identity(['id' => $root]));
        $row = new $entity(['id' => 1, 'development_id' => $this->development]);
        $row->setNew(false);
        $row->clean();

        $this->assertTrue($service->can($identity, 'edit', $row), 'control: the same resolver allows an edit');
        $this->assertFalse($service->can($identity, 'delete', $row));
    }

    /**
     * @return array<string, array{class-string, string|null, bool, bool, bool}>
     */
    public static function tableMatrix(): array
    {
        $cases = [];
        foreach ([UnitsTablePolicy::class, SectionsTablePolicy::class] as $policy) {
            foreach (self::actors() as $actor => [$role, $readOnly, $read, $write]) {
                $cases[substr($policy, strrpos($policy, '\\') + 1) . ": {$actor}"] = [$policy, $role, $readOnly, $read, $write];
            }
        }

        return $cases;
    }

    #[DataProvider('tableMatrix')]
    public function testATableIsListedWithUnitsViewAndOfferedForAddingWithUnitsManage(
        string $policy,
        ?string $role,
        bool $readOnly,
        bool $mayRead,
        bool $mayWrite,
    ): void {
        $user = $this->userWith($role, $readOnly);
        $guard = new $policy();

        $this->assertSame($mayRead, $guard->canIndex($user, null), 'index');
        $this->assertSame($mayWrite, $guard->canAdd($user, null), 'add');
    }

    #[DataProvider('tableMatrix')]
    public function testTheListIsNarrowedToTheDevelopmentsTheUserMayView(
        string $policy,
        ?string $role,
        bool $readOnly,
        bool $mayRead,
        bool $mayWrite,
    ): void {
        $other = $this->createDevelopment('Sur');
        $isUnits = $policy === UnitsTablePolicy::class;
        $isUnits ? $this->seedUnit($this->development, null, 'mine') : $this->seedSection($this->development, 'mine');
        $isUnits ? $this->seedUnit($other, null, 'theirs') : $this->seedSection($other, 'theirs');
        $user = $this->userWith($role, $readOnly);
        $query = $this->getTableLocator()->get($isUnits ? 'Units' : 'Sections')->find('all');

        $rows = (new $policy())->scopeIndex($user, $query)->all()->extract($isUnits ? 'number' : 'name')->toList();
        sort($rows);

        // A read-only super-administrator reads nothing at the database (is_super() needs a writable assignment).
        $isSuper = $role === 'super_administrator' && !$readOnly;
        $expected = $isSuper ? ['mine', 'theirs'] : ($mayRead && $role !== 'super_administrator' ? ['mine'] : []);
        $this->assertSame($expected, $rows);
    }
}
