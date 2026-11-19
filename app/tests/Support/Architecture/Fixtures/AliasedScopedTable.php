<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture\Fixtures;

use App\Model\Behavior\DevelopmentScopedBehavior;
use Cake\ORM\Table;

/**
 * Architecture fixture: the behavior loaded under another alias, and no bulk-operations trait (a violation).
 */
class AliasedScopedTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('fixture_aliased');
        $this->addBehavior('Anything', ['className' => DevelopmentScopedBehavior::class]);
    }
}
