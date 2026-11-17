<?php
namespace App\Test\TestCase\Security;

use App\Security\CondominiumAccess;
use Cake\TestSuite\TestCase;

class CondominiumAccessResolverTestController
{
    public function index($id_condominio = null)
    {
    }

    public function informe($id_condominio = null, $fecha = null)
    {
    }

    public function view($id = null)
    {
    }
}

/**
 * Which passed argument of an action carries a condominium id.
 */
class CondominiumAccessResolverTest extends TestCase
{
    public function testParameterNamedIdCondominioIsDetectedByName(): void
    {
        $this->assertSame(
            '7',
            CondominiumAccess::condominiumIdArgument(CondominiumAccessResolverTestController::class, 'Any', 'informe', ['7', '2025'])
        );
    }

    public function testPlainRecordIdIsNotTreatedAsCondominium(): void
    {
        $this->assertNull(
            CondominiumAccess::condominiumIdArgument(CondominiumAccessResolverTestController::class, 'Any', 'view', ['7'])
        );
    }

    public function testMisnamedParametersComeFromTheExplicitList(): void
    {
        $this->assertSame(
            '3',
            CondominiumAccess::condominiumIdArgument(CondominiumAccessResolverTestController::class, 'Comunicado', 'index', ['3'])
        );
        $this->assertSame(
            '4',
            CondominiumAccess::condominiumIdArgument(CondominiumAccessResolverTestController::class, 'Condomino', 'view', ['10', '4'])
        );
    }

    public function testMissingArgumentMeansNothingToCheck(): void
    {
        $this->assertNull(
            CondominiumAccess::condominiumIdArgument(CondominiumAccessResolverTestController::class, 'Any', 'index', [])
        );
    }
}
