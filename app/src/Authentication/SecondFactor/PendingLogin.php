<?php
declare(strict_types=1);

namespace App\Authentication\SecondFactor;

use Cake\Http\Session;

/**
 * The half-finished sign-in between a correct password and a correct second factor.
 *
 * It is a marker, not an identity: the session holds the user id, an expiry and a failure counter under its own
 * key. Authentication only ever reads the `Auth` key, so nothing in this class can authorize a request.
 *
 * The session id is renewed whenever the marker is created or abandoned, so an id observed before the password
 * step is useless afterwards. A completed sign-in renews it again when the identity is persisted.
 */
final class PendingLogin
{
    public const SESSION_KEY = 'TotpPending';

    /**
     * Seconds the user has to finish the second step.
     */
    public const LIFETIME = 300;

    /**
     * Wrong codes tolerated before the password is required again.
     */
    public const MAX_FAILURES = 5;

    /**
     * @param \Cake\Http\Session $session Session.
     * @param int $userId User who passed the password step.
     * @return void
     */
    public static function start(Session $session, int $userId): void
    {
        $session->renew();
        $session->write(self::SESSION_KEY, [
            'user_id' => $userId,
            'expires' => time() + self::LIFETIME,
            'failures' => 0,
            'redirect' => null,
        ]);
    }

    /**
     * @param \Cake\Http\Session $session Session.
     * @return array{user_id: int, expires: int, failures: int, redirect: string|null}|null The marker, or null
     *  when there is none, it is malformed, or it expired (an expired or malformed marker is removed).
     */
    public static function current(Session $session): ?array
    {
        $pending = $session->read(self::SESSION_KEY);
        if ($pending === null) {
            return null;
        }

        $valid = is_array($pending)
            && is_int($pending['user_id'] ?? null)
            && is_int($pending['expires'] ?? null)
            && is_int($pending['failures'] ?? null)
            && $pending['expires'] > time();
        if (!$valid) {
            $session->delete(self::SESSION_KEY);

            return null;
        }

        return [
            'user_id' => $pending['user_id'],
            'expires' => $pending['expires'],
            'failures' => $pending['failures'],
            'redirect' => is_string($pending['redirect'] ?? null) ? $pending['redirect'] : null,
        ];
    }

    /**
     * @param \Cake\Http\Session $session Session.
     * @param string|null $redirect Internal URL to open after sign-in, already validated.
     * @return void
     */
    public static function rememberRedirect(Session $session, ?string $redirect): void
    {
        if ($redirect !== null && $session->check(self::SESSION_KEY)) {
            $session->write(self::SESSION_KEY . '.redirect', $redirect);
        }
    }

    /**
     * @param \Cake\Http\Session $session Session.
     * @return int Failures so far, including this one.
     */
    public static function recordFailure(Session $session): int
    {
        $failures = (int)$session->read(self::SESSION_KEY . '.failures') + 1;
        $session->write(self::SESSION_KEY . '.failures', $failures);

        return $failures;
    }

    /**
     * Drop the marker after a successful second step. The caller persists the identity, which renews the id.
     *
     * @param \Cake\Http\Session $session Session.
     * @return void
     */
    public static function finish(Session $session): void
    {
        $session->delete(self::SESSION_KEY);
    }

    /**
     * Drop the marker and the session id, so the password is required again.
     *
     * @param \Cake\Http\Session $session Session.
     * @return void
     */
    public static function abandon(Session $session): void
    {
        $session->delete(self::SESSION_KEY);
        $session->renew();
    }
}
