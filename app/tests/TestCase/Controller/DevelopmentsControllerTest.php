<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SignsInTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * The developments list: default deny plus scoping, end to end.
 */
class DevelopmentsControllerTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SignsInTrait;

    public function testAnonymousVisitorIsSentToLogin(): void
    {
        $this->get('/developments');

        $this->assertRedirectContains('/login');
    }

    public function testSuperAdministratorSeesEveryDevelopment(): void
    {
        $this->createDevelopment('Residencial Norte');
        $this->createDevelopment('Residencial Sur');
        $userId = $this->signInAs('root');
        $this->assignRole($userId, 'super_administrator', null);

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseContains('Desarrollos');
        $this->assertResponseContains('Residencial Norte');
        $this->assertResponseContains('Residencial Sur');
    }

    public function testScopedUserOnlySeesTheirDevelopments(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $this->createDevelopment('Residencial Sur');
        $userId = $this->signInAs('ann');
        $this->assignRole($userId, 'administrator', $north);

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseContains('Residencial Norte');
        $this->assertResponseNotContains('Residencial Sur');
    }

    public function testNamesAreEscaped(): void
    {
        $dev = $this->createDevelopment('<script>alert(1)</script>');
        $userId = $this->signInAs('ann');
        $this->assignRole($userId, 'administrator', $dev);

        $this->get('/developments');

        $this->assertResponseOk();
        $this->assertResponseNotContains('<script>alert(1)</script>');
        $this->assertResponseContains('&lt;script&gt;');
    }

    public function testUserWithoutAssignmentsIsForbidden(): void
    {
        $this->createDevelopment('Residencial Norte');
        $this->signInAs('nobody');
        Configure::write('debug', false);

        $this->get('/developments');

        $this->assertResponseCode(403);
        $this->assertResponseNotContains('Residencial Norte');
    }

    public function testRoleWithoutTheViewPermissionIsForbidden(): void
    {
        $dev = $this->createDevelopment('Residencial Norte');
        $userId = $this->signInAs('gary');
        $this->assignRole($userId, 'guard', $dev);

        $this->get('/developments');

        $this->assertResponseCode(403);
    }

    public function testExpiredAssignmentIsForbidden(): void
    {
        $dev = $this->createDevelopment('Residencial Norte');
        $userId = $this->signInAs('ann');
        $this->assignRole($userId, 'administrator', $dev, -60, -1);

        $this->get('/developments');

        $this->assertResponseCode(403);
    }

    public function testOnlyGetIsRouted(): void
    {
        $userId = $this->signInAs('root');
        $this->assignRole($userId, 'super_administrator', null);

        $this->post('/developments', []);

        $this->assertResponseCode(404);
    }

    public function testHomeStaysAvailableToASignedInUserWithoutPermissions(): void
    {
        $this->signInAs('nobody');

        $this->get('/');

        $this->assertResponseOk();
    }
}
