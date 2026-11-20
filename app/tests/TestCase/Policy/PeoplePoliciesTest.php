<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Person;
use App\Policy\PeopleTablePolicy;
use App\Policy\PersonPolicy;
use App\Test\Support\DatabaseTestCase;
use Authentication\Identity;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use Authorization\Policy\OrmResolver;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Who may see and change people (spec people: "Read scope", "Create person", "Editing people"). Reading needs
 * `people.view`, so a resident-only user is refused, RFC included; writing needs `people.manage` and a development that
 * is not archived (W-E), the super-administrator included. Nothing allows deletion.
 *
 * A person has no development of its own: the policies judge it for the development the controller states with
 * `inDevelopment()`, and refuse a person for which none was stated.
 */
class PeoplePoliciesTest extends DatabaseTestCase
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
        foreach ($role === null ? [] : explode('+', $role) as $code) {
            $this->assignRole(
                $user,
                $code,
                $code === 'super_administrator' ? null : $this->development,
                readOnly: $readOnly,
            );
        }
        $this->actAs($user);

        return $this->identity($user);
    }

    private function person(): Person
    {
        return (new Person(['id' => 1, 'name' => 'Ana']))->inDevelopment($this->development);
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
            'read-only data_entry' => ['data_entry', true, true, false],
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

    #[DataProvider('actors')]
    public function testAPersonIsReadWithPeopleViewAndWrittenWithPeopleManage(
        ?string $role,
        bool $readOnly,
        bool $mayRead,
        bool $mayWrite,
    ): void {
        $user = $this->userWith($role, $readOnly);
        $guard = new PersonPolicy();

        $this->assertSame($mayRead, $guard->canView($user, $this->person()), 'view');
        $this->assertSame($mayWrite, $guard->canEdit($user, $this->person()), 'edit');
        $this->assertSame($mayWrite, $guard->canAdd($user, $this->person()), 'add');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function writers(): array
    {
        return ['super_administrator' => ['super_administrator'], 'administrator' => ['administrator'], 'data_entry' => ['data_entry']];
    }

    #[DataProvider('writers')]
    public function testAnArchivedDevelopmentKeepsReadsButRefusesEveryWrite(string $role): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->development]);
        $user = $this->userWith($role);
        $guard = new PersonPolicy();

        $this->assertTrue($guard->canView($user, $this->person()));
        $this->assertFalse($guard->canEdit($user, $this->person()));
        $this->assertFalse($guard->canAdd($user, $this->person()));
    }

    public function testAnAdministratorOfAnotherDevelopmentIsRefusedEverything(): void
    {
        $other = $this->createDevelopment('Sur');
        $user = $this->createUser('bob');
        $this->assignRole($user, 'administrator', $other);
        $this->actAs($user);
        $identity = $this->identity($user);
        $guard = new PersonPolicy();

        $this->assertFalse($guard->canView($identity, $this->person()));
        $this->assertFalse($guard->canEdit($identity, $this->person()));
        $this->assertFalse($guard->canAdd($identity, $this->person()));
    }

    public function testAPersonWithoutAStatedDevelopmentIsRefusedEverythingEvenToTheSuperAdministrator(): void
    {
        $root = $this->userWith('super_administrator');
        $guard = new PersonPolicy();
        // A development named in the data is not a statement: only `inDevelopment()` states it.
        $claimed = new Person(['id' => 1, 'name' => 'Ana', 'development_id' => $this->development, 'development' => 1]);

        foreach ([new Person([]), $claimed] as $person) {
            $this->assertFalse($guard->canView($root, $person));
            $this->assertFalse($guard->canEdit($root, $person));
            $this->assertFalse($guard->canAdd($root, $person));
        }
    }

    public function testTheDevelopmentIsJudgedAsItWasStatedAndNotAsAMalformedValue(): void
    {
        $root = $this->userWith('super_administrator');
        $person = $this->person();

        $this->assertTrue((new PersonPolicy())->canEdit($root, $person));
        $this->assertFalse((new PersonPolicy())->canEdit($root, (new Person(['id' => 1]))->inDevelopment(0)));
        $this->assertFalse((new PersonPolicy())->canEdit($root, (new Person(['id' => 1]))->inDevelopment(-5)));
    }

    public function testTheAuthorizationServiceDeniesDeletionToEveryone(): void
    {
        $service = new AuthorizationService(new OrmResolver());
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);
        $identity = new IdentityDecorator($service, new Identity(['id' => $root]));
        $row = $this->person();
        $row->setNew(false);
        $row->clean();

        $this->assertTrue($service->can($identity, 'edit', $row), 'control: the same resolver allows an edit');
        $this->assertFalse($service->can($identity, 'delete', $row));
    }

    #[DataProvider('actors')]
    public function testATableIsListedWithPeopleViewAndOfferedForAddingWithPeopleManage(
        ?string $role,
        bool $readOnly,
        bool $mayRead,
        bool $mayWrite,
    ): void {
        $user = $this->userWith($role, $readOnly);
        $guard = new PeopleTablePolicy();

        $this->assertSame($mayRead, $guard->canIndex($user, null), 'index');
        $this->assertSame($mayWrite, $guard->canAdd($user, null), 'add');
    }

    #[DataProvider('actors')]
    public function testTheListIsNarrowedToTheDevelopmentsTheUserMayView(
        ?string $role,
        bool $readOnly,
        bool $mayRead,
        bool $mayWrite,
    ): void {
        $other = $this->createDevelopment('Sur');
        $this->createPerson('mine', [$this->development]);
        $this->createPerson('theirs', [$other]);
        $user = $this->userWith($role, $readOnly);
        $query = $this->getTableLocator()->get('People')->find('all');

        $rows = (new PeopleTablePolicy())->scopeIndex($user, $query)->all()->extract('name')->toList();
        sort($rows);

        // A read-only super-administrator reads nothing at the database (is_super() needs a writable assignment).
        $isSuper = $role === 'super_administrator' && !$readOnly;
        $expected = $isSuper ? ['mine', 'theirs'] : ($mayRead && $role !== 'super_administrator' ? ['mine'] : []);
        $this->assertSame($expected, $rows);
    }

    public function testTheTableScopeIsTheViewScopeNotTheManageScope(): void
    {
        // Accountant: may view, may not manage. If the table policy handed the manage scope, the list would be empty.
        $other = $this->createDevelopment('Sur');
        $this->createPerson('mine', [$this->development]);
        $this->createPerson('theirs', [$other]);
        $user = $this->userWith('accountant');
        $query = $this->getTableLocator()->get('People')->find('all');

        $names = (new PeopleTablePolicy())->scopeIndex($user, $query)->all()->extract('name')->toList();

        $this->assertSame(['mine'], $names);
    }
}
