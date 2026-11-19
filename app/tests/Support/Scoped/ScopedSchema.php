<?php
declare(strict_types=1);

namespace App\Test\Support\Scoped;

/**
 * Name of the per-process schema that holds the scoped test tables, so no test table ever lands in `public`.
 */
final class ScopedSchema
{
    /**
     * @return string Schema name, unique to this PHP process.
     */
    public static function name(): string
    {
        return 'scoped_test_' . getmypid();
    }

    /**
     * @param string $table Table name.
     * @return string Schema-qualified table name, as `Table::setTable()` and the schema reflection expect.
     */
    public static function qualify(string $table): string
    {
        return self::name() . '.' . $table;
    }
}
