<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Exception;

use App\Model\Exception\DevelopmentScopeViolation;
use App\Model\Exception\MissingDevelopmentScopeException;
use Cake\TestSuite\TestCase;
use LogicException;
use RuntimeException;

/**
 * The two ways layer 1 refuses an operation. Neither message may carry row data: they end up in logs and error pages.
 */
class DevelopmentScopeExceptionsTest extends TestCase
{
    public function testAMissingScopeIsAProgrammingError(): void
    {
        $e = new MissingDevelopmentScopeException('units');

        $this->assertInstanceOf(LogicException::class, $e);
        $this->assertStringContainsString('units', $e->getMessage());
        $this->assertStringContainsString('developmentScope', $e->getMessage());
    }

    public function testAViolationIsARuntimeRefusalWithAGenericMessage(): void
    {
        $e = new DevelopmentScopeViolation();

        $this->assertInstanceOf(RuntimeException::class, $e);
        $this->assertSame('The operation targets a development outside the active scope.', $e->getMessage());
    }
}
