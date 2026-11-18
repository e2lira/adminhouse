<?php
declare(strict_types=1);

namespace App\Test\Support;

/**
 * Sign-in helpers for integration tests that extend DatabaseTestCase and use IntegrationTestTrait.
 */
trait SignsInTrait
{
    protected const PASSWORD = 'correct horse battery';

    /**
     * The integration harness starts every request with a fresh session, so the session written by the
     * previous request is handed to the next one explicitly, as a browser cookie would.
     *
     * @return void
     */
    protected function carrySession(): void
    {
        $this->_session = $_SESSION ?? [];
    }

    /**
     * Create an active user, sign them in and keep the session for the next requests.
     *
     * @param string $username Username.
     * @return int User id.
     */
    protected function signInAs(string $username): int
    {
        $userId = $this->createUser($username, self::PASSWORD);
        $this->enableCsrfToken();
        $this->post('/login', ['username' => $username, 'password' => self::PASSWORD]);
        $this->assertRedirect('/');
        $this->carrySession();

        return $userId;
    }
}
