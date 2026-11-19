<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authorization;

use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * The operation scope shared by every scoped table while one save or delete runs (AD4b, W-A, W-B).
 */
class DevelopmentScopeStackTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->assertNull(DevelopmentScopeStack::current(), 'The stack must be empty after every test.');
        parent::tearDown();
    }

    public function testItIsEmptyByDefault(): void
    {
        $this->assertNull(DevelopmentScopeStack::current());
    }

    public function testTheScopeIsActiveOnlyInsideTheOperation(): void
    {
        $scope = DevelopmentScope::only([1]);

        $inside = DevelopmentScopeStack::run($scope, fn() => DevelopmentScopeStack::current());

        $this->assertSame($scope, $inside);
        $this->assertNull(DevelopmentScopeStack::current());
    }

    public function testAThrowingOperationStillPopsItsScope(): void
    {
        try {
            DevelopmentScopeStack::run(DevelopmentScope::all(), function (): void {
                throw new RuntimeException('boom');
            });
            $this->fail('The exception must propagate.');
        } catch (RuntimeException) {
            $this->assertNull(DevelopmentScopeStack::current());
        }
    }

    public function testNestedOperationsRestoreTheOuterScope(): void
    {
        $outer = DevelopmentScope::only([1]);
        $inner = DevelopmentScope::only([2]);

        $seen = DevelopmentScopeStack::run($outer, function () use ($inner): array {
            $during = DevelopmentScopeStack::run($inner, fn() => DevelopmentScopeStack::current());

            return [$during, DevelopmentScopeStack::current()];
        });

        $this->assertSame([$inner, $outer], $seen);
    }

    public function testAnOperationWithoutScopeInheritsTheOuterOneAndAddsNothingWhenThereIsNone(): void
    {
        $outer = DevelopmentScope::only([1]);

        $this->assertNull(DevelopmentScopeStack::run(null, fn() => DevelopmentScopeStack::current()));
        $this->assertSame(
            $outer,
            DevelopmentScopeStack::run($outer, fn() => DevelopmentScopeStack::run(null, fn() => DevelopmentScopeStack::current())),
        );
    }
}
