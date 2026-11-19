<?php
declare(strict_types=1);

namespace App\Controller;

use App\Authorization\RoleAssignmentException;
use App\Authorization\RoleAssignmentManager;
use App\Authorization\RoleAssignmentNotAllowed;
use App\Model\Entity\Development;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Who holds which role in a development: list, grant and end (RB-R3).
 *
 * Authorization is the DevelopmentPolicy (`role_assignments.manage` in that development); the rules live in
 * RoleAssignmentManager. A development the user cannot manage answers 404, the same as a missing one.
 *
 * @property \App\Controller\Component\DevelopmentScopeComponent $DevelopmentScope
 */
class RoleAssignmentsController extends AppController
{
    /**
     * Spanish copy for each refusal reason.
     */
    private const MESSAGES = [
        RoleAssignmentException::USER_NOT_FOUND => 'No existe un usuario activo con ese nombre.',
        RoleAssignmentException::ROLE_NOT_FOUND => 'Elige un rol válido.',
        RoleAssignmentException::ROLE_NOT_GRANTABLE => 'No se puede otorgar ese rol desde esta pantalla.',
        RoleAssignmentException::PRIVILEGED_ROLE => 'Solo un superadministrador puede otorgar o terminar ese rol.',
        RoleAssignmentException::SELF_CHANGE => 'No puedes cambiar tus propios roles.',
        RoleAssignmentException::DATE_INVALID => 'Las fechas deben tener el formato AAAA-MM-DD.',
        RoleAssignmentException::PERIOD_INVALID => 'La fecha de término no puede ser anterior a la fecha de inicio.',
        RoleAssignmentException::DUPLICATE => 'Ese usuario ya tiene ese rol en este desarrollo.',
        RoleAssignmentException::ALREADY_ENDED => 'Esa asignación ya terminó.',
        RoleAssignmentException::RULE_VIOLATION => 'No se pudo guardar: la asignación incumple una regla del sistema.',
    ];

    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('DevelopmentScope');
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @return void
     */
    public function index(int|string $developmentId): void
    {
        $development = $this->managedDevelopment($developmentId);
        $this->prepare($development, []);
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @return \Cake\Http\Response|null
     */
    public function add(int|string $developmentId): ?Response
    {
        $this->request->allowMethod(['post']);
        $development = $this->managedDevelopment($developmentId);
        $data = (array)$this->request->getData();

        try {
            $this->manager()->grant(
                $this->actorId(),
                (int)$development->id,
                (string)($data['username'] ?? ''),
                (string)($data['role_id'] ?? ''),
                (string)($data['starts_on'] ?? ''),
                (string)($data['ends_on'] ?? ''),
                !empty($data['read_only']),
            );
        } catch (RoleAssignmentNotAllowed) {
            throw new NotFoundException();
        } catch (RoleAssignmentException $e) {
            $this->Flash->error(self::MESSAGES[$e->reason] ?? self::MESSAGES[RoleAssignmentException::RULE_VIOLATION]);
            $this->prepare($development, $data);

            return $this->render('index');
        }

        $this->Flash->success('Rol otorgado.');

        return $this->redirect($this->indexUrl((int)$development->id));
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string|int $assignmentId Assignment id from the URL.
     * @return \Cake\Http\Response
     */
    public function end(int|string $developmentId, int|string $assignmentId): Response
    {
        $this->request->allowMethod(['post']);
        $development = $this->managedDevelopment($developmentId);

        try {
            $this->manager()->end($this->actorId(), (int)$development->id, (int)$assignmentId);
            $this->Flash->success('Asignación terminada.');
        } catch (RoleAssignmentNotAllowed) {
            throw new NotFoundException();
        } catch (RoleAssignmentException $e) {
            if ($e->reason === RoleAssignmentException::ASSIGNMENT_NOT_FOUND) {
                throw new NotFoundException();
            }
            $this->Flash->error(self::MESSAGES[$e->reason] ?? self::MESSAGES[RoleAssignmentException::RULE_VIOLATION]);
        }

        return $this->redirect($this->indexUrl((int)$development->id));
    }

    /**
     * Load the development and authorize; every failure is the same 404.
     *
     * @param string|int $developmentId Development id from the URL.
     * @return \App\Model\Entity\Development
     */
    private function managedDevelopment(int|string $developmentId): Development
    {
        // The scope of `role_assignments.manage` is the whole rule: a development outside it and a nonexistent one
        // answer the same 404 (AC-MD-1). The entity is then read under that same scope, and authorize() records the
        // policy check that default deny requires; it agrees with the scope by construction.
        $id = $this->DevelopmentScope->development($developmentId, RoleAssignmentManager::PERMISSION);
        /** @var \App\Model\Entity\Development $development */
        $development = $this->fetchTable('Developments')->get(
            $id,
            developmentScope: $this->DevelopmentScope->for(RoleAssignmentManager::PERMISSION),
        );
        $this->Authorization->authorize($development, 'manageRoleAssignments');

        return $development;
    }

    /**
     * @param \App\Model\Entity\Development $development Development.
     * @param array<string, mixed> $form Submitted values to show again.
     * @return void
     */
    private function prepare(Development $development, array $form): void
    {
        $assignments = $this->manager()->listFor($this->actorId(), (int)$development->id);
        $roles = $this->manager()->assignableRoles($this->actorId(), (int)$development->id);
        $indexUrl = $this->indexUrl((int)$development->id);
        $this->set(compact('development', 'assignments', 'roles', 'form', 'indexUrl'));
    }

    /**
     * @param int $developmentId Development id.
     * @return string
     */
    private function indexUrl(int $developmentId): string
    {
        return '/developments/' . $developmentId . '/role-assignments';
    }

    /**
     * @return int
     */
    private function actorId(): int
    {
        return (int)$this->request->getAttribute('identity')->getIdentifier();
    }

    /**
     * @return \App\Authorization\RoleAssignmentManager
     */
    private function manager(): RoleAssignmentManager
    {
        return new RoleAssignmentManager();
    }
}
