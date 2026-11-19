<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use App\Model\Entity\Development;
use App\Policy\DevelopmentPolicy;
use App\Test\Support\ActingAccessControl;
use App\Test\Support\DatabaseTestCase;
use Authentication\Identity;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use Cake\I18n\DateTime;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * W-E: writes to units, people and occupancies of an archived development are refused by policy, the
 * super-administrator included (they must unarchive first).
 */
class DevelopmentArchivedWritesPolicyTest extends DatabaseTestCase
{
    private const PERMISSIONS = [
        'canManageUnits' => DevelopmentPolicy::UNITS_MANAGE,
        'canManagePeople' => DevelopmentPolicy::PEOPLE_MANAGE,
        'canManageOccupancies' => DevelopmentPolicy::OCCUPANCIES_MANAGE,
    ];

    /**
     * @return array<string, array{string}>
     */
    public static function abilities(): array
    {
        $cases = [];
        foreach (array_keys(self::PERMISSIONS) as $ability) {
            $cases[$ability] = [$ability];
        }

        return $cases;
    }

    private function identity(int $userId = 1): IdentityInterface
    {
        return new IdentityDecorator(
            $this->createStub(AuthorizationServiceInterface::class),
            new Identity(['id' => $userId]),
        );
    }

    private function development(bool $archived): Development
    {
        return new Development(['id' => 7, 'archived_at' => $archived ? DateTime::now() : null]);
    }

    /**
     * A resolver that grants every permission in development 7 (as the administrator and data_entry roles will hold
     * the three `.manage` codes), or in none.
     *
     * @param bool $granted Whether the user holds the permission in development 7.
     * @return \App\Authorization\AccessControl
     */
    private function access(bool $granted, bool $everywhere = false): AccessControl
    {
        $scope = $everywhere ? DevelopmentScope::all() : ($granted ? DevelopmentScope::only([7]) : DevelopmentScope::none());

        return new class ($scope) extends AccessControl {
            public function __construct(private DevelopmentScope $scope)
            {
                parent::__construct();
            }

            /**
             * @inheritDoc
             */
            public function allowedFor(int $userId, string $permission): DevelopmentScope
            {
                return $this->scope;
            }
        };
    }

    #[DataProvider('abilities')]
    public function testAnArchivedDevelopmentRefusesAHolderOfThePermission(string $ability): void
    {
        $policy = new DevelopmentPolicy(access: $this->access(true));

        $this->assertFalse($policy->{$ability}($this->identity(), $this->development(true)));
    }

    #[DataProvider('abilities')]
    public function testAnArchivedDevelopmentRefusesTheSuperAdministrator(string $ability): void
    {
        $policy = new DevelopmentPolicy(access: $this->access(true, everywhere: true));

        $this->assertFalse($policy->{$ability}($this->identity(), $this->development(true)));
    }

    #[DataProvider('abilities')]
    public function testAnUnarchivedDevelopmentAllowsAHolderOfThePermission(string $ability): void
    {
        $policy = new DevelopmentPolicy(access: $this->access(true));

        $this->assertTrue($policy->{$ability}($this->identity(), $this->development(false)));
    }

    #[DataProvider('abilities')]
    public function testAnUnarchivedDevelopmentRefusesWhoLacksThePermission(string $ability): void
    {
        $policy = new DevelopmentPolicy(access: $this->access(false));

        $this->assertFalse($policy->{$ability}($this->identity(), $this->development(false)));
    }

    #[DataProvider('abilities')]
    public function testAnEntityLoadedWithoutTheArchiveStateIsRefused(string $ability): void
    {
        $policy = new DevelopmentPolicy(access: $this->access(true, everywhere: true));

        $this->assertFalse($policy->{$ability}($this->identity(), new Development(['id' => 7])));
    }

    #[DataProvider('abilities')]
    public function testEachAbilityAsksForItsOwnPermissionCode(string $ability): void
    {
        $asked = [];
        $access = new class ($asked) extends AccessControl {
            /**
             * @param list<string> $asked Permission codes asked, by reference.
             */
            public function __construct(private array &$asked)
            {
                parent::__construct();
            }

            /**
             * @inheritDoc
             */
            public function allowedFor(int $userId, string $permission): DevelopmentScope
            {
                $this->asked[] = $permission;

                return DevelopmentScope::none();
            }
        };

        (new DevelopmentPolicy(access: $access))->{$ability}($this->identity(), $this->development(false));

        $this->assertSame([self::PERMISSIONS[$ability]], $asked);
    }

    #[DataProvider('abilities')]
    public function testARealSuperAdministratorIsAllowedUntilTheDevelopmentIsArchived(string $ability): void
    {
        $dev = $this->createDevelopment('A');
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $policy = new DevelopmentPolicy(access: new ActingAccessControl());

        $open = new Development(['id' => $dev, 'archived_at' => null]);
        $archived = new Development(['id' => $dev, 'archived_at' => DateTime::now()]);

        $this->assertTrue($policy->{$ability}($this->identity($root), $open));
        $this->assertFalse($policy->{$ability}($this->identity($root), $archived));
    }

    #[DataProvider('abilities')]
    public function testAHiddenArchiveFieldStillCountsAsLoaded(string $ability): void
    {
        $policy = new DevelopmentPolicy(access: $this->access(true));
        $open = $this->development(false)->setHidden(['archived_at']);
        $archived = $this->development(true)->setHidden(['archived_at']);

        $this->assertTrue($policy->{$ability}($this->identity(), $open));
        $this->assertFalse($policy->{$ability}($this->identity(), $archived));
    }

    #[DataProvider('abilities')]
    public function testAnArchiveStateChangedInMemoryDoesNotUnlockTheDevelopment(string $ability): void
    {
        $policy = new DevelopmentPolicy(access: $this->access(true));
        $development = new Development(['id' => 7, 'archived_at' => DateTime::now()]);
        $development->clean();
        $development->archived_at = null;

        $this->assertFalse($policy->{$ability}($this->identity(), $development));
    }
}
