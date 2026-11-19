<?php
declare(strict_types=1);

namespace App\Model\Table;

/**
 * How many scoped saves are running, across every table: nested association saves and rules that save run inside
 * the outermost one. Only the outermost save may turn a constraint violation into a field error, because it is the
 * only one that knows which entity the violation belongs to.
 */
final class ScopedSaveDepth
{
    private static int $depth = 0;

    /**
     * @return bool True when no scoped save is running.
     */
    public static function isOutermost(): bool
    {
        return self::$depth === 0;
    }

    /**
     * Run a save, counting it while it runs.
     *
     * @template T
     * @param callable(): T $work The save.
     * @return T
     * @throws \Throwable Whatever the save throws, after the count is restored.
     */
    public static function run(callable $work): mixed
    {
        self::$depth++;
        try {
            return $work();
        } finally {
            self::$depth--;
        }
    }
}
