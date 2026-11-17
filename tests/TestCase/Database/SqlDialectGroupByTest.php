<?php
namespace App\Test\TestCase\Database;

use App\Database\SqlDialect;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * PostgreSQL rejects selecting columns that are neither grouped nor aggregated,
 * which MySQL/MariaDB tolerate by returning an arbitrary value from the group.
 */
class SqlDialectGroupByTest extends TestCase
{
    public $fixtures = ['app.Gasto'];

    public function testMysqlQueryIsLeftUntouched(): void
    {
        $query = $this->aggregateWithoutGroup();
        $before = $query->clause('select');

        (new SqlDialect(new Mysql()))->relaxGroupBy($query);

        $this->assertSame($before, $query->clause('select'));
    }

    public function testPostgresWrapsPlainColumnsNextToAggregatesKeepingTheirResultKey(): void
    {
        $query = $this->aggregateWithoutGroup();

        (new SqlDialect(new Postgres()))->relaxGroupBy($query);
        $sql = $query->sql();

        $this->assertRegExp('/\(?ANY_VALUE\(F\.id_condominio\)\)? AS ["`]?F__id_condominio["`]?/', $sql);
        $this->assertRegExp('/\(?ANY_VALUE\(c\.categoria\)\)? AS ["`]?categoria["`]?/', $sql);
        $this->assertRegExp('/\(?SUM\(F\.gasto\)\)? AS ["`]?total["`]?/', $sql);
    }

    public function testPostgresKeepsGroupedColumnsAndGroupedAliases(): void
    {
        $gasto = TableRegistry::getTableLocator()->get('Gasto');
        $query = $gasto->find();
        $query->select([
            'F.id_condominio',
            'departamento' => 'F.referencia',
            'concepto' => 'F.concepto',
            'total' => $query->func()->sum('F.gasto'),
        ])->from('gasto as F')->group(['F.id_condominio', 'departamento']);

        (new SqlDialect(new Postgres()))->relaxGroupBy($query);
        $sql = $query->sql();

        $this->assertNotRegExp('/ANY_VALUE\(F\.id_condominio\)/', $sql);
        $this->assertNotRegExp('/ANY_VALUE\(F\.referencia\)/', $sql);
        $this->assertRegExp('/\(?ANY_VALUE\(F\.concepto\)\)? AS ["`]?concepto["`]?/', $sql);
    }

    public function testPostgresWrapsRawSqlExpressionsThatAreNotAggregates(): void
    {
        $gasto = TableRegistry::getTableLocator()->get('Gasto');
        $query = $gasto->find();
        $query->select([
            'mes' => 'CASE WHEN month(F.fecha) = 1 THEN F.gasto ELSE 0 END',
            'total' => 'SUM(F.gasto)',
        ])->from('gasto as F');

        (new SqlDialect(new Postgres()))->relaxGroupBy($query);
        $sql = $query->sql();

        $this->assertContains('ANY_VALUE(CASE WHEN month(F.fecha) = 1 THEN F.gasto ELSE 0 END)) AS "mes"', $sql);
        $this->assertNotContains('ANY_VALUE(SUM(F.gasto))', $sql);
    }

    public function testPostgresLeavesQueriesWithoutAggregatesOrGroupAlone(): void
    {
        $gasto = TableRegistry::getTableLocator()->get('Gasto');
        $query = $gasto->find()->select(['id_gasto', 'concepto']);
        $before = $query->clause('select');

        (new SqlDialect(new Postgres()))->relaxGroupBy($query);

        $this->assertSame($before, $query->clause('select'));
    }

    public function testPostgresKeepsTheColumnTypeOfWrappedColumns(): void
    {
        $gasto = TableRegistry::getTableLocator()->get('Gasto');
        $query = $gasto->find();
        $query->select([
            'cuando' => 'Gasto.fecha',
            'total' => $query->func()->sum('Gasto.gasto'),
        ]);

        (new SqlDialect(new Postgres()))->relaxGroupBy($query);
        $query->sql();

        $this->assertSame('date', $query->getSelectTypeMap()->type('cuando'));
    }

    public function testPostgresWrapsArithmeticAndKnowsMoreAggregates(): void
    {
        $gasto = TableRegistry::getTableLocator()->get('Gasto');
        $query = $gasto->find();
        $query->select([
            'doble' => 'F.gasto * 2',
            'todos' => 'ARRAY_AGG(F.concepto)',
            'total' => 'SUM(F.gasto)',
        ])->from('gasto as F');

        (new SqlDialect(new Postgres()))->relaxGroupBy($query);
        $sql = $query->sql();

        $this->assertContains('ANY_VALUE(F.gasto * 2)', $sql);
        $this->assertNotContains('ANY_VALUE(ARRAY_AGG', $sql);
    }

    public function testRelaxingTwiceDoesNotDoubleWrap(): void
    {
        $query = $this->aggregateWithoutGroup();
        $dialect = new SqlDialect(new Postgres());

        $dialect->relaxGroupBy($query);
        $dialect->relaxGroupBy($query);

        $this->assertNotContains('ANY_VALUE(ANY_VALUE', $query->sql());
    }

    private function aggregateWithoutGroup(): Query
    {
        $gasto = TableRegistry::getTableLocator()->get('Gasto');
        $query = $gasto->find();
        $query->select([
            'F.id_condominio',
            'categoria' => 'c.categoria',
            'total' => $query->func()->sum('F.gasto'),
        ])->from('gasto as F');

        return $query;
    }
}
