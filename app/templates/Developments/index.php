<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Development> $developments
 * @var \App\Authorization\DevelopmentScope $manageable
 * @var \App\Authorization\DevelopmentScope $managing
 */
$this->assign('title', 'Desarrollos');
$types = ['condominium' => 'Condominio', 'subdivision' => 'Fraccionamiento'];
?>
<h1>Desarrollos</h1>
<?php if ($managing->isAll()) : ?>
<p><a href="<?= h($this->Url->build('/developments/add')) ?>">Nuevo desarrollo</a></p>
<?php endif; ?>
<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($developments as $development) : ?>
            <?php $id = (int)$development->id; ?>
        <tr>
            <td><?= h($development->name) ?></td>
            <td><?= h($types[$development->type] ?? $development->type) ?></td>
            <td><?= $development->archived_at !== null ? 'Archivado' : 'Activo' ?></td>
            <td>
                <?php if ($manageable->allows($id)) : ?>
                    <a href="<?= h($this->Url->build('/developments/' . $id . '/role-assignments')) ?>">Roles de usuarios</a>
                <?php endif; ?>
                <?php if ($managing->allows($id)) : ?>
                    <a href="<?= h($this->Url->build('/developments/' . $id . '/edit')) ?>">Editar</a>
                    <?php if ($development->archived_at === null) : ?>
                        <?= $this->Form->postLink(
                            'Archivar',
                            '/developments/' . $id . '/archive',
                            ['confirm' => '¿Archivar este desarrollo? Sus datos se conservan.'],
                        ) ?>
                    <?php else : ?>
                        <?= $this->Form->postLink(
                            'Restaurar',
                            '/developments/' . $id . '/unarchive',
                            ['confirm' => '¿Restaurar este desarrollo?'],
                        ) ?>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
