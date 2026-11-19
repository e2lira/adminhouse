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
use Cake\ORM\Table;
use LogicException;

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
 * Contained queries never receive the parent's options, so nothing is inherited implicitly: a query that is not the
 * primary one needs an explicit `developmentScope` or it throws. The parent supplies it. When a scoped table runs a
 * query with a contain() it hands its scope, as that explicit option, to every contained association whose foreign
 * key links the two development columns (`development_id` to `development_id`, or `id` to `development_id` for a
 * development and its children) and whose target is a scoped, non-through table. Such an association cannot leave
 * the development by construction, and the target is still filtered by the scope on its own column. Anything else
 * (plain foreign keys, through-mode tables, matching() and joinWith(), unscoped parents) needs an explicit scope in
 * its query builder. An explicit scope on a contained query is kept but narrowed to the parent's: never widened.
 * Likewise an explicit scope never widens the operation scope that is running (DevelopmentScopeStack). An unscoped
 * parent's builder is trusted to pass its own scope: that is server code, and the all-development scope is only built
 * in src/Authorization (architecture test).
 *
 * Through-mode tables (people) are scoped by reads only. They have no development column to guard, so writes to them
 * are not guarded here: they are created and changed by their own paths, which must read the entity under a scope
 * first, and that read is what the scope enforces.
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
        $explicit = $options['developmentScope'] ?? null;
        $running = DevelopmentScopeStack::current();
        if ($explicit instanceof DevelopmentScope) {
            // An explicit scope may narrow the operation that is running, never widen it.
            $scope = $running !== null ? $running->intersect($explicit) : $explicit;
        } else {
            $scope = $primary ? $running : null;
        }
        if (!$scope instanceof DevelopmentScope) {
            throw new MissingDevelopmentScopeException($this->table()->getAlias());
        }

        $this->propagateScope($query, $scope);

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
        $primaryKey = $table->getPrimaryKey();
        if (!is_string($primaryKey)) {
            throw new LogicException(sprintf(
                'Table `%s` is scoped through `%s`, which needs a single-column primary key.',
                $table->getAlias(),
                (string)$through,
            ));
        }
        $links = $table->getConnection()->selectQuery([$key], (string)$through)->where([$field . ' IN' => $ids]);

        return [$table->getAlias() . '.' . $primaryKey . ' IN' => $links];
    }

    /**
     * Hand the scope, explicitly, to the contained associations that may receive it (see the class docblock).
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array> $query Query.
     * @param \App\Authorization\DevelopmentScope $scope Scope of this query.
     * @return void
     */
    private function propagateScope(SelectQuery $query, DevelopmentScope $scope): void
    {
        if ($this->getConfig('through') !== null) {
            return;
        }

        $paths = [];
        $this->collectLinked($this->table(), $query->getContain(), '', $paths);
        foreach ($paths as $path) {
            $query->contain($path, function (SelectQuery $contained) use ($scope): SelectQuery {
                $own = $contained->getOptions()['developmentScope'] ?? null;

                return $contained->applyOptions([
                    'developmentScope' => $own instanceof DevelopmentScope ? $own->intersect($scope) : $scope,
                ]);
            });
        }
    }

    /**
     * @param \Cake\ORM\Table $source Table the containments hang from.
     * @param array<array-key, mixed> $contain Containments of `$source`.
     * @param string $prefix Dotted path of `$source`, with a trailing dot.
     * @param list<string> $paths Collected dotted paths of the associations that receive the scope.
     * @return void
     */
    private function collectLinked(Table $source, array $contain, string $prefix, array &$paths): void
    {
        foreach ($contain as $name => $options) {
            if (!is_string($name) || !is_array($options) || !$source->hasAssociation($name)) {
                continue;
            }

            $association = $source->getAssociation($name);
            if (!$this->linksDevelopment($source, $association)) {
                continue;
            }

            $paths[] = $prefix . $name;
            $this->collectLinked($association->getTarget(), $options, $prefix . $name . '.', $paths);
        }
    }

    /**
     * Whether the association equates the development column of its source with that of its target, and both tables
     * are scoped by a development column of their own (not through a link table).
     *
     * @param \Cake\ORM\Table $source Source table.
     * @param \Cake\ORM\Association $association Association to check.
     * @return bool
     */
    private function linksDevelopment(Table $source, Association $association): bool
    {
        $target = $association->getTarget();
        $from = $this->developmentColumn($source);
        $to = $this->developmentColumn($target);
        $key = $association->getForeignKey();
        $foreignKey = $key === false ? [] : (array)$key;
        if ($from === null || $to === null || $foreignKey === []) {
            return false;
        }

        $bindingKey = (array)$association->getBindingKey();
        [$sourceColumns, $targetColumns] = match ($association->type()) {
            Association::MANY_TO_ONE => [$foreignKey, $bindingKey],
            Association::ONE_TO_ONE, Association::ONE_TO_MANY => [$bindingKey, $foreignKey],
            default => [[], []],
        };

        foreach ($sourceColumns as $index => $column) {
            if ($column === $from && ($targetColumns[$index] ?? null) === $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Cake\ORM\Table $table Table.
     * @return string|null The development column of a scoped, non-through table; null otherwise.
     */
    private function developmentColumn(Table $table): ?string
    {
        if (!$table->hasBehavior('DevelopmentScoped')) {
            return null;
        }

        $behavior = $table->getBehavior('DevelopmentScoped');

        return $behavior->getConfig('through') === null ? (string)$behavior->getConfig('field') : null;
    }
}
