<?php
declare(strict_types=1);

namespace App\Test\TestCase\Config;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Cookie hardening from the slice 1 security review.
 */
class SecurityConfigTest extends TestCase
{
    use IntegrationTestTrait;

    public function testSessionCookieIsHardened(): void
    {
        $ini = Configure::read('Session.ini');

        $this->assertIsBool(Configure::read('App.cookieSecure'));
        $this->assertSame(1, $ini['session.cookie_httponly']);
        $this->assertSame('Lax', $ini['session.cookie_samesite']);
        $this->assertSame(Configure::read('App.cookieSecure') ? 1 : 0, $ini['session.cookie_secure']);
    }

    public function testCsrfCookieIsHardened(): void
    {
        $this->get('/login');

        $cookie = $this->_response->getCookie('csrfToken');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie['httponly']);
        $this->assertSame('Lax', $cookie['samesite']);
        $this->assertSame((bool)Configure::read('App.cookieSecure'), $cookie['secure']);
    }
}
