<?php
namespace App\Test\TestCase\Database;

use App\Database\SqlDialect;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\TestSuite\TestCase;

/**
 * The MySQL expectations are the exact fragments the controllers used before
 * PostgreSQL support was added, so production SQL must stay byte-identical.
 */
class SqlDialectTest extends TestCase
{
    public function testDetectsPostgresDriverOnly(): void
    {
        $this->assertTrue((new SqlDialect(new Postgres()))->isPostgres());
        $this->assertFalse((new SqlDialect(new Mysql()))->isPostgres());
        $this->assertFalse((new SqlDialect(new Sqlite()))->isPostgres());
    }

    public function testLeadingIntegerKeepsMysqlUnsignedCast(): void
    {
        $dialect = new SqlDialect(new Mysql());

        $this->assertSame(
            'CAST(numero_departamento AS UNSIGNED)',
            $dialect->leadingInteger('numero_departamento')
        );
    }

    public function testLeadingIntegerOnPostgresParsesLeadingDigitsAndDefaultsToZero(): void
    {
        $dialect = new SqlDialect(new Postgres());

        $this->assertSame(
            "COALESCE(CAST(SUBSTRING(c.numero_departamento FROM '^[0-9]+') AS BIGINT), 0)",
            $dialect->leadingInteger('c.numero_departamento')
        );
    }

    public function testIntegerAsTextIsUntouchedOnMysql(): void
    {
        $this->assertSame('c.id_tipo', (new SqlDialect(new Mysql()))->integerAsText('c.id_tipo'));
    }

    public function testIntegerAsTextCastsOnPostgres(): void
    {
        $this->assertSame(
            'CAST(c.id_tipo AS VARCHAR)',
            (new SqlDialect(new Postgres()))->integerAsText('c.id_tipo')
        );
    }

    public function testTextAsNumberIsUntouchedOnMysql(): void
    {
        $this->assertSame('gasto', (new SqlDialect(new Mysql()))->textAsNumber('gasto'));
    }

    public function testTextAsNumberCastsOnPostgresTreatingEmptyAsNull(): void
    {
        $this->assertSame(
            "CAST(NULLIF(gasto, '') AS NUMERIC)",
            (new SqlDialect(new Postgres()))->textAsNumber('gasto')
        );
    }
}
