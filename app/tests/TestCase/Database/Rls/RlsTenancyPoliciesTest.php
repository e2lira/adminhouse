<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

/**
 * Policies of `developments`, `people`, `person_developments` and `users` for the runtime role.
 */
class RlsTenancyPoliciesTest extends RlsTestCase
{
    // developments ------------------------------------------------------------------------------------------------

    public function testNoSessionSeesNoDevelopment(): void
    {
        $this->actAs(null);

        $this->assertSame([], $this->visibleIds('developments'));
    }

    public function testSuperAdministratorSeesEveryDevelopment(): void
    {
        $this->actAs($this->superUser);

        $this->assertSame([$this->devA, $this->devB], $this->visibleIds('developments'));
    }

    public function testEachRoleOfADevelopmentSeesOnlyThatDevelopment(): void
    {
        foreach (['adminA', 'accountantA', 'guardA', 'resident'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->devA], $this->visibleIds('developments'), $user);
            $this->endSession();
        }
        $this->actAs($this->adminB);
        $this->assertSame([$this->devB], $this->visibleIds('developments'));
    }

    public function testDisabledEndedFutureAndAssignmentlessUsersSeeNoDevelopment(): void
    {
        foreach (['disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([], $this->visibleIds('developments'), $user);
            $this->endSession();
        }
    }

    public function testJobSeesOnlyItsDevelopments(): void
    {
        $this->actAsJob([$this->devB]);
        $this->assertSame([$this->devB], $this->visibleIds('developments'));
        $this->endSession();

        $this->actAsJob([]);
        $this->assertSame([], $this->visibleIds('developments'));
    }

    public function testOnlySuperAdministratorWritesDevelopments(): void
    {
        $insert = "INSERT INTO developments (type, name, section_label, number_label, created, modified)
                   VALUES ('condominium', 'New', 'Torre', 'Depto', NOW(), NOW())";

        foreach (['adminA', 'accountantA', 'resident', 'disabledAdmin', 'endedAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertRejected($insert);
            $this->assertDenied('UPDATE developments SET name = ? WHERE id = ?', ['Hacked', $this->devA]);
            $this->assertDenied('DELETE FROM developments WHERE id = ?', [$this->devA]);
            $this->endSession();
        }
        $this->actAsJob([$this->devA]);
        $this->assertRejected($insert);
        $this->endSession();
        $this->actAs(null);
        $this->assertRejected($insert);
        $this->endSession();

        $this->actAs($this->superUser);
        $this->assertAllowed($insert);
        $this->assertAllowed('UPDATE developments SET name = ? WHERE id = ?', ['Renamed', $this->devB]);
        $this->assertAllowed('DELETE FROM developments WHERE id = ?', [$this->devB]);
    }

    public function testOnlySuperAdministratorWritesTheProfileAndArchiveColumns(): void
    {
        $update = 'UPDATE developments SET address = ?, phone = ?, rfc = ?, bank_name = ?, bank_account = ?,
                   archived_at = ? WHERE id = ?';
        $read = 'SELECT address, phone, rfc, bank_name, bank_account, archived_at FROM developments WHERE id = ?';
        $untouched = [
            'address' => null, 'phone' => null, 'rfc' => null, 'bank_name' => null, 'bank_account' => null,
            'archived_at' => null,
        ];

        foreach (['adminA', 'accountantA', 'resident'] as $user) {
            $this->actAs($this->{$user});
            $this->assertDenied($update, ['Calle 1', '555', 'RFC', 'Banco', '0123', '2026-01-01 00:00:00', $this->devA]);
            $visible = $this->appDb()->execute($read, [$this->devA])->fetch('assoc');
            $this->assertSame($untouched, $visible, $user);
            $this->endSession();
        }

        $this->actAs($this->superUser);
        $this->assertAllowed($update, ['Calle 1', '555', 'RFC', 'Banco', '0123', '2026-01-01 00:00:00', $this->devA]);
        $this->assertSame([
            'address' => 'Calle 1', 'phone' => '555', 'rfc' => 'RFC', 'bank_name' => 'Banco',
            'bank_account' => '0123', 'archived_at' => '2026-01-01 00:00:00',
        ], $this->appDb()->execute($read, [$this->devA])->fetch('assoc'));
    }

    // people ------------------------------------------------------------------------------------------------------

    public function testNoSessionSeesNoPeople(): void
    {
        $this->actAs(null);

        $this->assertSame([], $this->visibleIds('people'));
        $this->assertSame([], $this->visibleIds('person_developments'));
    }

    public function testPeopleAreVisibleThroughTheirDevelopmentsOrAsTheOwnRow(): void
    {
        $this->actAs($this->adminA);
        $this->assertSame([$this->personResident, $this->personShared], $this->visibleIds('people'));
        $this->endSession();

        $this->actAs($this->adminB);
        $this->assertSame([$this->personB, $this->personShared], $this->visibleIds('people'));
        $this->endSession();

        $this->actAs($this->superUser);
        $this->assertSame(
            [$this->personResident, $this->personB, $this->personShared],
            $this->visibleIds('people'),
        );
        $this->endSession();

        // people follow people.view (A2), which is request-only: a job reads no person, whatever its developments.
        $this->actAsJob([$this->devB]);
        $this->assertSame([], $this->visibleIds('people'));
    }

    public function testOwnPersonStaysVisibleWithoutAnyAssignment(): void
    {
        $this->db()->execute('DELETE FROM role_assignments WHERE user_id = ?', [$this->resident]);
        $this->actAs($this->resident);

        $this->assertSame([$this->personResident], $this->visibleIds('people'));
    }

    public function testLinksToOtherDevelopmentsStayHidden(): void
    {
        $this->actAs($this->adminA);

        $links = $this->appDb()->execute('SELECT person_id, development_id FROM person_developments')->fetchAll('assoc');
        $this->assertSame([$this->devA], array_values(array_unique(array_map('intval', array_column($links, 'development_id')))));
    }

    public function testOwnLinksAreVisibleEvenInAnotherDevelopment(): void
    {
        $this->db()->execute('UPDATE users SET person_id = ? WHERE id = ?', [$this->personShared, $this->resident]);
        $this->actAs($this->resident);

        $developments = $this->ids('SELECT development_id FROM person_developments WHERE person_id = ?', [
            $this->personShared,
        ]);
        $this->assertSame([$this->devA, $this->devB], $developments);
    }

    public function testNobodyInsertsPeopleOrLinksDirectly(): void
    {
        foreach ([$this->superUser, $this->adminA] as $user) {
            $this->actAs($user);
            $this->assertRejected("INSERT INTO people (name, created, modified) VALUES ('X', NOW(), NOW())");
            $this->assertRejected(
                'INSERT INTO person_developments (person_id, development_id) VALUES (?, ?)',
                [$this->personB, $this->devA],
            );
            $this->assertRejected('DELETE FROM person_developments WHERE person_id = ?', [$this->personResident]);
            $this->assertRejected('UPDATE person_developments SET development_id = ? WHERE person_id = ?', [
                $this->devB,
                $this->personResident,
            ]);
            $this->endSession();
        }
    }

    public function testPersonCreateByAnAdministratorOfThatDevelopment(): void
    {
        $this->actAs($this->adminA);

        $id = (int)$this->appDb()->execute(
            "SELECT person_create(?, 'New Person', 'n@example.test', NULL, NULL)",
            [$this->devA],
        )->fetch()[0];

        $this->assertContains($id, $this->visibleIds('people'));
        $this->assertSame([$this->devA], $this->ids('SELECT development_id FROM person_developments WHERE person_id = ?', [$id]));
    }

    public function testPersonCreateIsRefusedOutsideTheAdministeredDevelopments(): void
    {
        $this->actAs($this->adminA);
        $this->assertRejected("SELECT person_create(?, 'X', NULL, NULL, NULL)", [$this->devB]);
        $this->endSession();

        foreach (['accountantA', 'resident', 'guardA', 'disabledAdmin', 'endedAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertRejected("SELECT person_create(?, 'X', NULL, NULL, NULL)", [$this->devA]);
            $this->endSession();
        }
        $this->actAs(null);
        $this->assertRejected("SELECT person_create(?, 'X', NULL, NULL, NULL)", [$this->devA]);
        $this->endSession();
        $this->actAsJob([$this->devA]);
        $this->assertRejected("SELECT person_create(?, 'X', NULL, NULL, NULL)", [$this->devA]);

        $this->assertSame(3, (int)$this->db()->execute('SELECT COUNT(*) FROM people')->fetch()[0]);
    }

    public function testSuperAdministratorCreatesPeopleAnywhere(): void
    {
        $this->actAs($this->superUser);

        $id = (int)$this->appDb()->execute("SELECT person_create(?, 'Anywhere', NULL, NULL, NULL)", [$this->devB])->fetch()[0];

        $this->assertContains($id, $this->visibleIds('people'));
    }

    public function testPersonCreateIsAtomic(): void
    {
        $this->actAs($this->adminA);
        $this->assertRejected('SELECT person_create(?, NULL, NULL, NULL, NULL)', [$this->devA]);

        $this->assertSame(3, (int)$this->db()->execute('SELECT COUNT(*) FROM people')->fetch()[0]);
    }

    public function testPeopleUpdates(): void
    {
        // The person's own contact fields.
        $this->actAs($this->resident);
        $this->assertAllowed("UPDATE people SET phone = '555' WHERE id = ?", [$this->personResident]);
        $this->assertDenied("UPDATE people SET phone = '555' WHERE id = ?", [$this->personShared]);
        $this->commitSession();

        // An administrator edits people linked only to developments they administer.
        $this->actAs($this->adminA);
        $this->assertAllowed("UPDATE people SET phone = '777' WHERE id = ?", [$this->personResident]);
        $this->assertDenied("UPDATE people SET phone = '777' WHERE id = ?", [$this->personShared]);
        $this->assertDenied("UPDATE people SET phone = '777' WHERE id = ?", [$this->personB]);
        $this->commitSession();

        // Shared people are edited by the super-administrator.
        $this->actAs($this->superUser);
        $this->assertAllowed("UPDATE people SET phone = '888' WHERE id = ?", [$this->personShared]);
        $this->commitSession();

        foreach (['accountantA', 'guardA', 'disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertDenied("UPDATE people SET phone = '999' WHERE id = ?", [$this->personResident]);
            $this->endSession();
        }
        $this->actAs(null);
        $this->assertDenied("UPDATE people SET phone = '999' WHERE id = ?", [$this->personResident]);
        $this->endSession();

        $phones = $this->db()->execute('SELECT id, phone FROM people ORDER BY id')->fetchAll('assoc');
        $this->assertSame(
            ['777', null, '888'],
            array_column($phones, 'phone'),
        );
    }

    public function testOnlyTheSuperAdministratorDeletesPeople(): void
    {
        $this->actAs($this->adminA);
        $this->assertDenied('DELETE FROM people WHERE id = ?', [$this->personResident]);
        $this->endSession();

        $this->actAs($this->superUser);
        $this->assertAllowed('DELETE FROM people WHERE id = ?', [$this->personResident]);
    }

    // users -------------------------------------------------------------------------------------------------------

    public function testNoSessionSeesNoUser(): void
    {
        $this->actAs(null);

        $this->assertSame([], $this->visibleIds('users'));
    }

    public function testAUserSeesOnlyTheirOwnRow(): void
    {
        $this->actAs($this->loner);
        $this->assertSame([$this->loner], $this->visibleIds('users'));
        $this->endSession();

        $this->actAs($this->accountantA);
        $this->assertSame([$this->accountantA], $this->visibleIds('users'));
        $this->endSession();

        $this->actAs($this->resident);
        $this->assertSame([$this->resident], $this->visibleIds('users'));
    }

    public function testAnAdministratorSeesUsersAssignedInTheirDevelopments(): void
    {
        $this->actAs($this->adminA);

        $this->assertSame(
            [
                $this->adminA,
                $this->accountantA,
                $this->dataEntryA,
                $this->guardA,
                $this->resident,
                $this->disabledAdmin,
                $this->endedAdmin,
                $this->futureAdmin,
            ],
            $this->visibleIds('users'),
        );
    }

    public function testSuperAdministratorSeesEveryUser(): void
    {
        $this->actAs($this->superUser);

        $all = (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0];
        $this->assertGreaterThan(1, $all);
        $this->assertCount($all, $this->visibleIds('users'));
    }

    public function testDisabledEndedAndFutureAdministratorsSeeNoOneButNotEvenThemselvesWhenDisabled(): void
    {
        $this->actAs($this->disabledAdmin);
        $this->assertSame([], $this->visibleIds('users'));
        $this->endSession();

        $this->actAs($this->endedAdmin);
        $this->assertSame([$this->endedAdmin], $this->visibleIds('users'));
        $this->endSession();

        $this->actAs($this->futureAdmin);
        $this->assertSame([$this->futureAdmin], $this->visibleIds('users'));
    }

    public function testJobContextSeesNoUser(): void
    {
        $this->actAsJob([$this->devA, $this->devB]);

        $this->assertSame([], $this->visibleIds('users'));
    }

    public function testNobodyWritesUsersDirectly(): void
    {
        foreach ([$this->superUser, $this->adminA, $this->loner] as $user) {
            $this->actAs($user);
            $this->assertRejected(
                "INSERT INTO users (username, created, modified) VALUES ('mallory', NOW(), NOW())",
            );
            $this->assertRejected("UPDATE users SET status = 'disabled' WHERE id = ?", [$user]);
            $this->assertRejected('UPDATE users SET person_id = ? WHERE id = ?', [$this->personB, $user]);
            $this->assertRejected('DELETE FROM users WHERE id = ?', [$user]);
            $this->endSession();
        }

        $this->assertSame(0, (int)$this->db()->execute("SELECT COUNT(*) FROM users WHERE username = 'mallory'")->fetch()[0]);
    }
}
