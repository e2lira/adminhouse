<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Cake\Datasource\EntityInterface> $developments
 * @var \App\Authorization\DevelopmentScope $manageable
 */
$this->assign('title', 'Desarrollos');
$types = ['condominium' => 'Condominio', 'subdivision' => 'Fraccionamiento'];
?>
<h1>Desarrollos</h1>
<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($developments as $development) : ?>
        <tr>
            <td><?= h($development->name) ?></td>
            <td><?= h($types[$development->type] ?? $development->type) ?></td>
            <td>
                <?php if ($manageable->allows((int)$development->id)) : ?>
                    <a href="<?= h($this->Url->build('/developments/' . (int)$development->id . '/role-assignments')) ?>">Roles de usuarios</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
