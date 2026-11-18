<?php
declare(strict_types=1);

namespace App\Test\Support\Scoped;

use Cake\ORM\Table;

/**
 * Test-only table whose own `id` is the development (`scoped_realms`), standing in for developments.
 */
class RealmsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable(ScopedSchema::qualify('scoped_realms'));
        $this->addBehavior('DevelopmentScoped', ['field' => 'id']);
    }
}
