<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use App\Model\Exception\MissingDevelopmentScopeException;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;

/**
 * Layer 1 of ADR-0005: restricts a table to the developments the acting user may reach, and fails closed.
 *
 * The scope travels explicitly as the `developmentScope` option (a DevelopmentScope). A primary find without it, and
 * without a running operation scope (see DevelopmentScopeStack), throws instead of returning every row.
 *
 * Configuration:
 * - `field`: the column holding the development (default `development_id`; `id` for developments themselves).
 * - `through`: a link table for tables with no development column (people): rows are visible when the link table
 *   joins them to an allowed development. `throughKey` names the link table column that points back (default
 *   `<field of the table>`: `person_id`), and `field` is read in the link table.
 *
 * Contained (association) queries do not receive the parent's options. They inherit the parent's scope only when this
 * table is joined from a scoped table through a composite `(development_id, x_id)` foreign key, which cannot leave the
 * development by construction. Through-mode tables and every other contained query need an explicit scope.
 */
class DevelopmentScopedBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'field' => 'development_id',
        'through' => null,
        'throughKey' => null,
    ];

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array> $query Query.
     * @param \ArrayObject<string, mixed> $options Query options.
     * @param bool $primary Whether this is the main query (false when contained).
     * @return void
     * @throws \App\Model\Exception\MissingDevelopmentScopeException When no scope can be resolved.
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
        $scope = $options['developmentScope'] ?? ($primary ? DevelopmentScopeStack::current() : null);
        if (!$scope instanceof DevelopmentScope) {
            if ($primary || !$this->inheritsScope()) {
                throw new MissingDevelopmentScopeException($this->table()->getAlias());
            }

            return;
        }

        if ($scope->isAll()) {
            return;
        }
        if ($scope->isEmpty()) {
            $query->where(['1 = 0']);

            return;
        }

        $query->where($this->restriction($scope->ids()));
    }

    /**
     * @param list<int> $ids Allowed development ids (never empty).
     * @return array<string, mixed> Conditions.
     */
    private function restriction(array $ids): array
    {
        $table = $this->table();
        $through = $this->getConfig('through');
        $field = (string)$this->getConfig('field');
        if ($through === null) {
            return [$table->getAlias() . '.' . $field . ' IN' => $ids];
        }

        $key = (string)($this->getConfig('throughKey') ?? 'person_id');
        $links = $table->getConnection()->selectQuery([$key], (string)$through)->where([$field . ' IN' => $ids]);

        return [$table->getAlias() . '.' . (string)$table->getPrimaryKey() . ' IN' => $links];
    }

    /**
     * Whether a contained query on this table may skip the filter: it is not through-mode and it is joined from a
     * scoped table through a composite foreign key that includes the development column.
     *
     * @return bool
     */
    private function inheritsScope(): bool
    {
        if ($this->getConfig('through') !== null) {
            return false;
        }

        $field = (string)$this->getConfig('field');
        foreach ($this->table()->associations() as $association) {
            $foreignKey = $association->getForeignKey();
            if (
                $association->type() === Association::MANY_TO_ONE
                && $association->getTarget()->hasBehavior('DevelopmentScoped')
                && is_array($foreignKey)
                && in_array($field, $foreignKey, true)
            ) {
                return true;
            }
        }

        return false;
    }
}
