<?php
declare(strict_types=1);

namespace App\Test\Support\Scoped;

use App\Model\Table\ScopedBulkOperationsTrait;
use App\Model\Table\ScopedOperationTrait;
use Cake\ORM\Table;

/**
 * Test-only table scoped through a link table (`scoped_person_links`), standing in for people.
 */
class PeopleTable extends Table
{
    use ScopedBulkOperationsTrait;
    use ScopedOperationTrait;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable(ScopedSchema::qualify('scoped_people'));
        $this->addBehavior('DevelopmentScoped', ['through' => 'scoped_person_links', 'throughKey' => 'person_id']);
    }
}
