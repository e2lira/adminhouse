<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use Cake\Controller\Component;
use Cake\Http\Exception\NotFoundException;

/**
 * The developments the signed-in user may act on, for the controller to hand to the scoped tables (AD5).
 *
 * `for()` asks AccessControl once per user and permission. `development()` answers 404 for a development outside
 * that scope, for one that does not exist and for a malformed id alike, so the response never tells which ids exist
 * (AC-MD-1).
 *
 * Config: `access`, an AccessControl to use instead of the default one.
 */
class DevelopmentScopeComponent extends Component
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'access' => null,
    ];

    /**
     * Scopes already resolved, by `<user id>:<permission>`: the identity is part of the key, so a component that
     * outlives a change of identity never answers for the previous user.
     *
     * @var array<string, \App\Authorization\DevelopmentScope>
     */
    private array $scopes = [];

    /**
     * @param string $permission Permission code, for example `developments.view`.
     * @return \App\Authorization\DevelopmentScope Where the user holds it today; empty without an identity (that
     *  answer is never remembered).
     */
    public function for(string $permission): DevelopmentScope
    {
        $identity = $this->getController()->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return DevelopmentScope::none();
        }

        $userId = (int)$identity->getIdentifier();
        $key = $userId . ':' . $permission;
        if (!isset($this->scopes[$key])) {
            $access = $this->getConfig('access') ?? new AccessControl();
            $this->scopes[$key] = $access->allowedFor($userId, $permission);
        }

        return $this->scopes[$key];
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string $permission Permission code the action needs.
     * @return int The id, when the development exists and the user holds the permission in it.
     * @throws \Cake\Http\Exception\NotFoundException Otherwise, with no hint of which reason applies.
     */
    public function development(int|string $developmentId, string $permission): int
    {
        $id = $this->parseId($developmentId);
        $found = $id === null ? null : $this->getController()->fetchTable('Developments')
            ->find('all', developmentScope: $this->for($permission))
            ->select(['Developments.id'])
            ->where(['Developments.id' => $id])
            ->first();
        if ($found === null) {
            throw new NotFoundException();
        }

        return $id;
    }

    /**
     * @param string|int $value Id as received.
     * @return int|null The id when it is a canonical positive integer that fits a bigint, otherwise null.
     */
    private function parseId(int|string $value): ?int
    {
        $text = (string)$value;
        // An overflowing number saturates on the cast and no longer matches its text.
        if (preg_match('/^[1-9][0-9]*$/', $text) !== 1 || (string)(int)$text !== $text) {
            return null;
        }

        return (int)$text;
    }
}
