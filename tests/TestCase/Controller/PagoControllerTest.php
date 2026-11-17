<?php
namespace App\Test\TestCase\Controller;

use App\Controller\PagoController;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class PagoControllerTest extends TestCase
{
    private $actionDispatches = 0;

    public function testAuthorizedProfilesCanAccessPaymentControllerPolicy(): void
    {
        $controller = $this->controller();

        foreach ([1, 2, 5] as $profile) {
            $this->assertTrue($controller->isAuthorized(['id_perfil' => $profile]));
        }

        $this->assertNoPaymentActionDispatch();
    }

    public function testOtherProfilesAreDeniedByPaymentControllerPolicy(): void
    {
        $controller = $this->controller();

        foreach ([3, 4, 6, 99] as $profile) {
            $this->assertFalse($controller->isAuthorized(['id_perfil' => $profile]));
        }

        $this->assertNoPaymentActionDispatch();
    }

    private function controller(): PagoController
    {
        $test = $this;

        return new class(new ServerRequest(['url' => '/pago']), new Response(), $test) extends PagoController {
            private $testCase;

            public function __construct(ServerRequest $request, Response $response, PagoControllerTest $testCase)
            {
                parent::__construct($request, $response);
                $this->testCase = $testCase;
            }

            public function index($id_condominio = null)
            {
                $this->testCase->recordPaymentActionDispatch();
                throw new \LogicException('SEC-001 must not dispatch PagoController::index().');
            }

            public function add($id_condominio = null)
            {
                $this->testCase->recordPaymentActionDispatch();
                throw new \LogicException('SEC-001 must not dispatch PagoController::add().');
            }
        };
    }

    public function recordPaymentActionDispatch(): void
    {
        ++$this->actionDispatches;
    }

    private function assertNoPaymentActionDispatch(): void
    {
        $this->assertSame(0, $this->actionDispatches, 'Payment actions must not be dispatched.');
    }
}
