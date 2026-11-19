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
 * `for()` asks AccessControl once per permission and request. `development()` answers 404 for a development outside
 * that scope and for one that does not exist alike, so the response never tells which ids exist (AC-MD-1).
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
     * @var array<string, \App\Authorization\DevelopmentScope>
     */
    private array $scopes = [];

    /**
     * @param string $permission Permission code, for example `developments.view`.
     * @return \App\Authorization\DevelopmentScope Where the user holds it today; empty without an identity.
     */
    public function for(string $permission): DevelopmentScope
    {
        if (isset($this->scopes[$permission])) {
            return $this->scopes[$permission];
        }

        $identity = $this->getController()->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return $this->scopes[$permission] = DevelopmentScope::none();
        }

        $access = $this->getConfig('access') ?? new AccessControl();

        return $this->scopes[$permission] = $access->allowedFor((int)$identity->getIdentifier(), $permission);
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string $permission Permission code the action needs.
     * @return int The id, when the development exists and the user holds the permission in it.
     * @throws \Cake\Http\Exception\NotFoundException Otherwise, with no hint of which reason applies.
     */
    public function development(int|string $developmentId, string $permission): int
    {
        $id = (int)$developmentId;
        $found = $this->getController()->fetchTable('Developments')
            ->find('all', developmentScope: $this->for($permission))
            ->select(['Developments.id'])
            ->where(['Developments.id' => $id])
            ->first();
        if ($found === null) {
            throw new NotFoundException();
        }

        return $id;
    }
}
