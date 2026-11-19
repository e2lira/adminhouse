<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authorization;

use App\Authorization\DevelopmentScope;
use Cake\TestSuite\TestCase;

/**
 * Narrowing a scope by another: an inner scope may only ever shrink the outer one.
 */
class DevelopmentScopeIntersectionTest extends TestCase
{
    public function testAllIsTheIdentity(): void
    {
        $this->assertSame([1, 2], DevelopmentScope::all()->intersect(DevelopmentScope::only([1, 2]))->ids());
        $this->assertSame([1, 2], DevelopmentScope::only([1, 2])->intersect(DevelopmentScope::all())->ids());
        $this->assertTrue(DevelopmentScope::all()->intersect(DevelopmentScope::all())->isAll());
    }

    public function testOnlyKeepsTheCommonDevelopments(): void
    {
        $result = DevelopmentScope::only([1, 2, 3])->intersect(DevelopmentScope::only([2, 3, 4]));

        $this->assertSame([2, 3], $result->ids());
    }

    public function testDisjointOrEmptyScopesMatchNothing(): void
    {
        $this->assertTrue(DevelopmentScope::only([1])->intersect(DevelopmentScope::only([2]))->isEmpty());
        $this->assertTrue(DevelopmentScope::none()->intersect(DevelopmentScope::all())->isEmpty());
        $this->assertTrue(DevelopmentScope::all()->intersect(DevelopmentScope::none())->isEmpty());
    }
}
