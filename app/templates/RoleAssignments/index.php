<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Development $development
 * @var list<array<string, mixed>> $assignments
 * @var list<array{id: int, code: string, name: string}> $roles
 * @var array<string, mixed> $form
 * @var string $indexUrl
 */
$this->assign('title', 'Roles de usuarios');
$states = ['current' => 'Vigente', 'future' => 'Futura', 'ended' => 'Terminada'];
?>
<p><a href="<?= h($this->Url->build('/developments')) ?>">&larr; Desarrollos</a></p>
<h1>Roles de usuarios</h1>
<h2><?= h($development->name) ?></h2>

<table>
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Solo lectura</th>
            <th>Estado</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($assignments as $assignment) : ?>
        <tr>
            <td><?= h($assignment['username']) ?></td>
            <td><?= h($assignment['role_name']) ?></td>
            <td><?= h($assignment['starts_on']) ?></td>
            <td><?= h($assignment['ends_on'] ?? '—') ?></td>
            <td><?= $assignment['read_only'] ? 'Sí' : 'No' ?></td>
            <td><?= h($states[$assignment['state']] ?? $assignment['state']) ?></td>
            <td>
                <?php if ($assignment['endable']) : ?>
                    <?= $this->Form->postLink(
                        'Terminar',
                        $indexUrl . '/' . (int)$assignment['id'] . '/end',
                        ['confirm' => '¿Terminar esta asignación?'],
                    ) ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($assignments === []) : ?>
        <tr><td colspan="7">Todavía no hay asignaciones en este desarrollo.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Otorgar un rol</h2>
<?= $this->Form->create(null, ['url' => $indexUrl]) ?>
<?= $this->Form->control('username', [
    'label' => 'Usuario',
    'required' => true,
    'autocomplete' => 'off',
    'value' => (string)($form['username'] ?? ''),
]) ?>
<?= $this->Form->control('role_id', [
    'label' => 'Rol',
    'type' => 'select',
    'options' => array_column($roles, 'name', 'id'),
    'empty' => 'Elige un rol',
    'required' => true,
    'value' => (string)($form['role_id'] ?? ''),
]) ?>
<?= $this->Form->control('starts_on', [
    'label' => 'Desde (vacío: hoy)',
    'type' => 'date',
    'value' => (string)($form['starts_on'] ?? ''),
]) ?>
<?= $this->Form->control('ends_on', [
    'label' => 'Hasta (opcional)',
    'type' => 'date',
    'value' => (string)($form['ends_on'] ?? ''),
]) ?>
<?= $this->Form->control('read_only', [
    'label' => 'Solo lectura',
    'type' => 'checkbox',
    'checked' => !empty($form['read_only']),
]) ?>
<?= $this->Form->button('Otorgar') ?>
<?= $this->Form->end() ?>
