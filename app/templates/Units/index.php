<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Development $development
 * @var iterable<\App\Model\Entity\Unit> $units
 * @var bool $canManage
 * @var array<string, string> $types
 */
$this->assign('title', 'Unidades');
$this->loadHelper('Paginator');
$base = '/developments/' . (int)$development->id . '/units';
?>
<p><a href="<?= h($this->Url->build('/developments')) ?>">&larr; Desarrollos</a></p>
<h1>Unidades de <?= h($development->name) ?></h1>
<?php if ($canManage) : ?>
<p><a href="<?= h($this->Url->build($base . '/add')) ?>">Nueva unidad</a></p>
    <?= $this->Form->create(null, ['url' => $base . '/sections']) ?>
    <?= $this->Form->control('name', [
    'label' => 'Nueva sección (' . $development->section_label . ')',
    'maxlength' => 150,
    'required' => true,
    ]) ?>
    <?= $this->Form->button('Crear sección') ?>
    <?= $this->Form->end() ?>
<?php endif; ?>
<table>
    <thead>
        <tr>
            <th><?= h($development->section_label) ?></th>
            <th><?= h($development->number_label) ?></th>
            <th>Tipo</th>
            <th>Estado</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($units as $unit) : ?>
            <?php $unitUrl = $base . '/' . (int)$unit->id; ?>
        <tr>
            <td><?= h($unit->section?->name) ?></td>
            <td><a href="<?= h($this->Url->build($unitUrl)) ?>"><?= h($unit->number) ?></a></td>
            <td><?= h($types[$unit->type] ?? $unit->type) ?></td>
            <td><?= $unit->is_active ? 'Activa' : 'Inactiva' ?></td>
            <td>
                <?php if ($canManage) : ?>
                    <a href="<?= h($this->Url->build($unitUrl . '/edit')) ?>">Editar</a>
                    <?php if ($unit->is_active) : ?>
                        <?= $this->Form->postLink(
                            'Desactivar',
                            $unitUrl . '/deactivate',
                            ['confirm' => '¿Desactivar esta unidad? Se conserva con su historial.'],
                        ) ?>
                    <?php else : ?>
                        <?= $this->Form->postLink('Reactivar', $unitUrl . '/activate') ?>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<p>
    <?= $this->Paginator->prev('Anterior') ?>
    <?= $this->Paginator->next('Siguiente') ?>
</p>
