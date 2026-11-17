<?php
namespace App\Database;

use Cake\Database\Driver\Postgres;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\TypedResultInterface;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;

/**
 * SQL fragments that differ between MySQL/MariaDB and PostgreSQL.
 *
 * Every method returns the fragment the application already used on MySQL
 * unless the active driver is PostgreSQL, so MySQL queries stay unchanged.
 */
class SqlDialect
{
    const AGGREGATES = [
        'SUM', 'COUNT', 'MIN', 'MAX', 'AVG', 'ANY_VALUE', 'GROUP_CONCAT', 'STRING_AGG', 'ARRAY_AGG',
        'BOOL_AND', 'BOOL_OR', 'BIT_AND', 'BIT_OR', 'STDDEV', 'STDDEV_POP', 'STDDEV_SAMP', 'VARIANCE',
        'VAR_POP', 'VAR_SAMP', 'JSON_AGG', 'JSON_ARRAYAGG', 'JSON_OBJECTAGG',
    ];

    /**
     * @var bool
     */
    private $postgres;

    /**
     * @param object $driver Driver of the connection the SQL will run on.
     */
    public function __construct($driver)
    {
        $this->postgres = $driver instanceof Postgres;
    }

    /**
     * @param string $connection Connection name.
     * @return self
     */
    public static function forConnection($connection = 'default')
    {
        return new self(ConnectionManager::get($connection)->getDriver());
    }

    /**
     * @return bool
     */
    public function isPostgres()
    {
        return $this->postgres;
    }

    /**
     * Integer value of the leading digits of a text expression; 0 when there are none.
     *
     * @param string $expression Text column or expression.
     * @return string
     */
    public function leadingInteger($expression)
    {
        if ($this->postgres) {
            return "COALESCE(CAST(SUBSTRING($expression FROM '^[0-9]+') AS BIGINT), 0)";
        }

        return "CAST($expression AS UNSIGNED)";
    }

    /**
     * Integer expression that is compared against a text column.
     *
     * @param string $expression Integer column or expression.
     * @return string
     */
    public function integerAsText($expression)
    {
        return $this->postgres ? "CAST($expression AS VARCHAR)" : $expression;
    }

    /**
     * Text expression holding a number that is used in arithmetic or aggregates.
     *
     * @param string $expression Text column or expression.
     * @return string
     */
    public function textAsNumber($expression)
    {
        return $this->postgres ? "CAST(NULLIF($expression, '') AS NUMERIC)" : $expression;
    }

    /**
     * Makes MySQL-style "loose GROUP BY" selects valid on PostgreSQL.
     *
     * MySQL/MariaDB (without ONLY_FULL_GROUP_BY) return an arbitrary row value for
     * selected columns that are neither grouped nor aggregated. PostgreSQL rejects
     * them, so on PostgreSQL those columns are wrapped in ANY_VALUE() (PostgreSQL 16+),
     * which has the same meaning. Result keys are preserved. No-op on other drivers.
     *
     * @param \Cake\Database\Query $query Select query, before execution.
     * @return \Cake\Database\Query
     */
    public function relaxGroupBy($query)
    {
        if (!$this->postgres) {
            return $query;
        }

        $select = $query->clause('select');
        $groups = [];
        foreach ((array)$query->clause('group') as $group) {
            if (is_string($group)) {
                $groups[] = $this->normalize($group);
            }
        }

        $hasAggregate = false;
        foreach ($select as $value) {
            if ($this->isAggregate($value)) {
                $hasAggregate = true;
                break;
            }
        }
        if (!$hasAggregate && empty($groups)) {
            return $query;
        }

        $changed = false;
        $relaxed = [];
        foreach ($select as $key => $value) {
            $isGrouped = (is_string($value) && in_array($this->normalize($value), $groups, true))
                || (is_string($key) && in_array($this->normalize($key), $groups, true));
            $isStar = is_string($value) && preg_match('/(^|\.)\*$/', trim($value)) === 1;
            if ($isGrouped || $isStar || $this->isAggregate($value)) {
                $relaxed[$key] = $value;
                continue;
            }

            // Keep the result key the ORM would have generated ("F.col" becomes "F__col").
            $alias = $key;
            if (!is_string($key) && is_string($value) && method_exists($query, 'aliasField')) {
                $alias = key($query->aliasField($value));
            }
            // Keep the original column type so results are still cast (dates, booleans, decimals).
            $type = null;
            if (is_string($value)) {
                $type = $query->getTypeMap()->type($value);
            } elseif ($value instanceof TypedResultInterface) {
                $type = $value->getReturnType();
            }
            $relaxed[$alias] = new FunctionExpression(
                'ANY_VALUE',
                is_string($value) ? [$value => 'literal'] : [$value],
                [],
                $type ?: 'string'
            );
            $changed = true;
        }

        if ($changed) {
            $query->select($relaxed, true);
        }

        return $query;
    }

    /**
     * @param mixed $value Select clause value.
     * @return bool
     */
    private function isAggregate($value)
    {
        if ($value instanceof FunctionExpression
            && in_array(strtoupper($value->getName()), self::AGGREGATES, true)) {
            return true;
        }
        if ($value instanceof ExpressionInterface) {
            $value = $value->sql(new ValueBinder());
        }

        return is_string($value)
            && preg_match('/\b(' . implode('|', self::AGGREGATES) . ')\s*\(/i', $value) === 1;
    }

    /**
     * @param string $identifier Column, alias, or expression.
     * @return string
     */
    private function normalize($identifier)
    {
        return strtolower(str_replace(['"', '`', ' '], '', $identifier));
    }
}
