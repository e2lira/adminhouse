<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Development;
use App\Policy\DevelopmentPolicy;
use App\Test\Support\ActingAccessControl;
use App\Test\Support\DatabaseTestCase;
use Authentication\Identity;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Only the super-administrator edits and archives developments (D1, `developments.manage`).
 */
class DevelopmentPolicyTest extends DatabaseTestCase
{
    private DevelopmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DevelopmentPolicy(access: new ActingAccessControl());
    }

    /**
     * @param int $userId User id.
     * @return \Authorization\IdentityInterface
     */
    private function identity(int $userId): IdentityInterface
    {
        return new IdentityDecorator(
            $this->createStub(AuthorizationServiceInterface::class),
            new Identity(['id' => $userId]),
        );
    }

    /**
     * @param int $id Development id.
     * @return \App\Model\Entity\Development
     */
    private function development(int $id): Development
    {
        return new Development(['id' => $id]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function abilities(): array
    {
        return ['canEdit' => ['canEdit'], 'canArchive' => ['canArchive']];
    }

    #[DataProvider('abilities')]
    public function testASuperAdministratorMay(string $ability): void
    {
        $dev = $this->createDevelopment('A');
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);

        $this->assertTrue($this->policy->{$ability}($this->identity($root), $this->development($dev)));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function refusedRoles(): array
    {
        $roles = ['administrator', 'accountant', 'data_entry', 'supervisor', 'guard', 'resident'];
        $cases = [];
        foreach (['canEdit', 'canArchive'] as $ability) {
            foreach ($roles as $role) {
                $cases["{$ability} {$role}"] = [$ability, $role, false];
            }
            $cases["{$ability} read-only administrator"] = [$ability, 'administrator', true];
        }

        return $cases;
    }

    #[DataProvider('refusedRoles')]
    public function testAnyOtherRoleIsRefusedEvenInThatDevelopment(string $ability, string $role, bool $readOnly): void
    {
        $dev = $this->createDevelopment('A');
        $user = $this->createUser('ann');
        $this->assignRole($user, $role, $dev, readOnly: $readOnly);

        $this->assertFalse($this->policy->{$ability}($this->identity($user), $this->development($dev)));
    }

    public function testAUserWithoutAssignmentsIsRefused(): void
    {
        $dev = $this->createDevelopment('A');
        $loner = $this->createUser('loner');

        $this->assertFalse($this->policy->canEdit($this->identity($loner), $this->development($dev)));
        $this->assertFalse($this->policy->canArchive($this->identity($loner), $this->development($dev)));
    }

    /**
     * @return array<string, array{int, int|null, bool, string, bool}>
     */
    public static function superAssignments(): array
    {
        // startsInDays, endsInDays, readOnly, user status, expected
        return [
            'open' => [-30, null, false, 'active', true],
            'ends today' => [-30, 0, false, 'active', true],
            'expired yesterday' => [-30, -1, false, 'active', false],
            'starts in the future' => [5, null, false, 'active', false],
            'read-only' => [-30, null, true, 'active', false],
            'disabled user' => [-30, null, false, 'disabled', false],
        ];
    }

    #[DataProvider('superAssignments')]
    public function testASuperAdministratorIsAllowedOnlyWhileTheAssignmentIsActive(
        int $startsInDays,
        ?int $endsInDays,
        bool $readOnly,
        string $status,
        bool $expected,
    ): void {
        $dev = $this->createDevelopment('A');
        $root = $this->createUser('root', null, $status);
        $this->assignRole($root, 'super_administrator', null, $startsInDays, $endsInDays, $readOnly);

        $this->assertSame($expected, $this->policy->canEdit($this->identity($root), $this->development($dev)));
        $this->assertSame($expected, $this->policy->canArchive($this->identity($root), $this->development($dev)));
    }
}
