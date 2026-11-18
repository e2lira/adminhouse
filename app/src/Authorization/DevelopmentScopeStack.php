<?php
declare(strict_types=1);

namespace App\Authorization;

/**
 * The scope of the save or delete that is running, shared by every scoped table (AD4b).
 *
 * `Table::exists()` runs from `IsUnique` and `ExistsIn` rules before `beforeSave`, and the target of an `ExistsIn`
 * rule or of a nested association save is another table. None of those finds receives the caller's options, so the
 * operation's scope is published here for the duration of the operation and only then.
 *
 * Pushing and popping are paired by run(), which pops in `finally`: a rule or listener that throws can never leave
 * a scope behind for the next, unrelated find (W-A).
 */
final class DevelopmentScopeStack
{
    /**
     * @var list<\App\Authorization\DevelopmentScope>
     */
    private static array $stack = [];

    /**
     * @return \App\Authorization\DevelopmentScope|null The scope of the innermost running operation.
     */
    public static function current(): ?DevelopmentScope
    {
        return self::$stack === [] ? null : self::$stack[array_key_last(self::$stack)];
    }

    /**
     * Run an operation under a scope. Without one the operation inherits the running scope, if any.
     *
     * @template T
     * @param \App\Authorization\DevelopmentScope|null $scope Scope of the operation.
     * @param callable(): T $work The operation.
     * @return T
     * @throws \Throwable Whatever the operation throws, after the scope is popped.
     */
    public static function run(?DevelopmentScope $scope, callable $work): mixed
    {
        $scope ??= self::current();
        if ($scope === null) {
            return $work();
        }

        self::$stack[] = $scope;
        try {
            return $work();
        } finally {
            array_pop(self::$stack);
        }
    }
}
