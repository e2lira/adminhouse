<?php
declare(strict_types=1);

use App\Database\RowLevelSecurity as Rls;
use Migrations\BaseMigration;

/**
 * People are read through `people.view` instead of any assignment (design amendment A2, user decision 2026-11-19).
 *
 * Replaces two SELECT policies. Before, anyone with an assignment in a development (a resident, a guard) read every
 * person linked to it, and every `person_developments` link of it; the permission catalogue had no effect on reads.
 *
 * - `people_select`: `own row OR is_super OR the person has a link to a development in permission_dev_ids('people.view')`.
 * - `person_developments_select`: `development_id in permission_dev_ids('people.view') OR own links OR is_super`.
 *   Left on `dev_ids()` it would let a resident list every person id and link of the development.
 *
 * Effects (all pinned by tests): a resident or guard reads only their own person (and its links); a read-only
 * assignment still reads (the code ends in `.view`); an archived development is still read (view codes keep archived
 * ones); a job reads no person at all (`permission_dev_ids` is request-only). Write implies read still holds because
 * `people.manage` is only ever seeded together with `people.view` (AccessControlTest).
 *
 * Nothing else changes: UPDATE, DELETE, grants and every function stay as they are. `down()` restores the two
 * `dev_ids()` policies exactly as 20261119000003 created them.
 */
class PeopleReadByPermission extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->replacePolicies(Rls::permissionDevIds('people.view'));
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->replacePolicies(Rls::DEV_IDS);
    }

    /**
     * @param string $ids Predicate-side array of the developments a person or link is read through.
     * @return void
     */
    private function replacePolicies(string $ids): void
    {
        $super = Rls::IS_SUPER;
        $person = Rls::PERSON_ID;
        $linked = "EXISTS (SELECT 1 FROM public.person_developments pd
                           WHERE pd.person_id = people.id AND pd.development_id = ANY ({$ids}))";

        $this->execute('DROP POLICY people_select ON people');
        $this->execute(Rls::policy('people', 'SELECT', "id = {$person} OR {$super} OR {$linked}"));
        $this->execute('DROP POLICY person_developments_select ON person_developments');
        $this->execute(Rls::policy(
            'person_developments',
            'SELECT',
            "development_id = ANY ({$ids}) OR person_id = {$person} OR {$super}",
        ));
    }
}
