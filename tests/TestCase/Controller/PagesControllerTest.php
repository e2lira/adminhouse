<?php
namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Characterizes PagesController as this application uses it: every page needs a
 * signed-in user with profile 1 to 5 (replaces the CakePHP skeleton test, which
 * assumed public pages and a CSRF-only POST flow).
 *
 * @uses \App\Controller\PagesController
 */
class PagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function testRootShowsTheLoginFormOnRepeatedRequests(): void
    {
        $this->get('/');
        $this->assertResponseOk();
        $this->get('/');
        $this->assertResponseOk();
    }

    public function testPagesRequireSignIn(): void
    {
        $this->get('/pages/home');

        $this->assertRedirectContains('redirect=');
    }

    public function testSignedInStaffCanDisplayAPage(): void
    {
        $this->signInAs(1);

        $this->get('/pages/home');

        $this->assertResponseOk();
        $this->assertResponseContains('<html');
    }

    public function testResidentProfileIsRefused(): void
    {
        $this->signInAs(6);

        $this->get('/pages/home');

        $this->assertRedirect();
    }

    public function testMissingTemplateIsNotFoundInProduction(): void
    {
        $this->signInAs(1);
        Configure::write('debug', false);

        $this->get('/pages/not_existing');

        $this->assertResponseCode(404);
    }

    public function testMissingTemplateInDebugShowsTheError(): void
    {
        $this->signInAs(1);
        Configure::write('debug', true);

        $this->get('/pages/not_existing');

        $this->assertResponseFailure();
        $this->assertResponseContains('Missing Template');
        $this->assertResponseContains('not_existing.ctp');
    }

    public function testDirectoryTraversalIsForbidden(): void
    {
        $this->signInAs(1);

        $this->get('/pages/../Layout/ajax');

        $this->assertResponseCode(403);
    }

    private function signInAs(int $profile): void
    {
        $this->session(['Auth' => ['User' => ['id_user' => 1, 'id_perfil' => $profile]]]);
    }
}
