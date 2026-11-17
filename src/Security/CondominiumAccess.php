<?php
namespace App\Security;

use Cake\Controller\Controller;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * Interim condominium check (AZ-2) until RBAC with development scopes (ADR-0005) replaces it.
 *
 * When an action receives a condominium id in the URL, the signed-in user must belong to
 * that condominium: staff through `users_condominio`, residents through the unit assigned
 * in `asigna`. The Superuser profile sees every condominium. Anything else answers 404.
 */
class CondominiumAccess
{
    const SUPERUSER_PROFILE = 1;

    /**
     * Action parameter names that always carry a condominium id.
     */
    const NAMED_PARAMETERS = ['id_condominio', 'condominio'];

    /**
     * Actions whose condominium id parameter has another name, found by code review on
     * 2026-11-17 (controller => action => position of the passed argument).
     */
    const EXPLICIT = [
        'Banco' => ['index' => 0],
        'Comunicado' => ['index' => 0],
        'Condomino' => ['index' => 0, 'view' => 1],
        'Mensaje' => ['index' => 0],
        'Condominio' => ['view' => 0, 'edit' => 0, 'delete' => 0, 'borrar' => 0, 'menu' => 0],
    ];

    /**
     * Rejects the request when its condominium id does not belong to the signed-in user.
     *
     * @param \Cake\Controller\Controller $controller Running controller.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public static function enforce(Controller $controller)
    {
        if (!isset($controller->Auth)) {
            return;
        }
        $user = $controller->Auth->user();
        if (empty($user['id_user']) || (int)($user['id_perfil'] ?? 0) === self::SUPERUSER_PROFILE) {
            return;
        }

        $request = $controller->getRequest();
        $condominiumId = self::condominiumIdArgument(
            get_class($controller),
            $controller->getName(),
            (string)$request->getParam('action'),
            (array)$request->getParam('pass')
        );
        if ($condominiumId === null) {
            return;
        }

        if (!in_array((int)$condominiumId, self::allowedCondominiumIds((int)$user['id_user']), true)) {
            throw new NotFoundException();
        }
    }

    /**
     * @param string $controllerClass Controller class name.
     * @param string $controllerName Controller name, for example "Comunicado".
     * @param string $action Action name.
     * @param array $pass Passed URL arguments.
     * @return string|null The condominium id argument, or null when the action has none.
     */
    public static function condominiumIdArgument($controllerClass, $controllerName, $action, array $pass)
    {
        $position = null;
        if (isset(self::EXPLICIT[$controllerName][$action])) {
            $position = self::EXPLICIT[$controllerName][$action];
        } elseif (method_exists($controllerClass, $action)) {
            foreach ((new \ReflectionMethod($controllerClass, $action))->getParameters() as $index => $parameter) {
                if (in_array($parameter->getName(), self::NAMED_PARAMETERS, true)) {
                    $position = $index;
                    break;
                }
            }
        }

        if ($position === null || !isset($pass[$position]) || (string)$pass[$position] === '') {
            return null;
        }

        return (string)$pass[$position];
    }

    /**
     * @param int $userId Signed-in user id.
     * @return int[] Condominium ids the user belongs to.
     */
    public static function allowedCondominiumIds($userId)
    {
        $locator = TableRegistry::getTableLocator();
        $staff = $locator->get('UsersCondominio')->find()
            ->select(['id_condominio'])
            ->where(['id_user' => $userId])
            ->enableHydration(false)
            ->extract('id_condominio')
            ->toList();
        $residents = $locator->get('Asigna')->find()
            ->select(['id_condominio' => 'c.id_condominio'])
            ->join(['c' => ['table' => 'condomino', 'type' => 'INNER', 'conditions' => 'c.id_condomino = Asigna.id_condomino']])
            ->where(['Asigna.id_user' => $userId])
            ->enableHydration(false)
            ->extract('id_condominio')
            ->toList();

        return array_values(array_unique(array_map('intval', array_merge($staff, $residents))));
    }
}
