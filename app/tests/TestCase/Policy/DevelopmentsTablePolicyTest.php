<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Policy\DevelopmentsTablePolicy;
use App\Test\Support\ActingAccessControl;
use App\Test\Support\DatabaseTestCase;
use Authentication\Identity;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Who may add a development: only an all-development `developments.manage` scope, i.e. the super-administrator.
 */
class DevelopmentsTablePolicyTest extends DatabaseTestCase
{
    private DevelopmentsTablePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DevelopmentsTablePolicy(new ActingAccessControl());
    }

    private function identity(int $userId): IdentityInterface
    {
        return new IdentityDecorator(
            $this->createStub(AuthorizationServiceInterface::class),
            new Identity(['id' => $userId]),
        );
    }

    public function testASuperAdministratorMayAdd(): void
    {
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);

        $this->assertTrue($this->policy->canAdd($this->identity($root), null));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function refused(): array
    {
        return [
            'administrator' => ['administrator', false],
            'data_entry' => ['data_entry', false],
            'read-only administrator' => ['administrator', true],
            'accountant' => ['accountant', false],
        ];
    }

    #[DataProvider('refused')]
    public function testAnyoneElseIsRefused(string $role, bool $readOnly): void
    {
        $dev = $this->createDevelopment('A');
        $user = $this->createUser('ann');
        $this->assignRole($user, $role, $dev, readOnly: $readOnly);

        $this->assertFalse($this->policy->canAdd($this->identity($user), null));
    }

    public function testAReadOnlySuperAdministratorIsRefused(): void
    {
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null, readOnly: true);

        $this->assertFalse($this->policy->canAdd($this->identity($root), null));
    }

    public function testAnExpiredSuperAdministratorIsRefused(): void
    {
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null, -30, -1);

        $this->assertFalse($this->policy->canAdd($this->identity($root), null));
    }

    public function testAUserWithoutAssignmentsIsRefused(): void
    {
        $this->assertFalse($this->policy->canAdd($this->identity($this->createUser('loner')), null));
    }
}
