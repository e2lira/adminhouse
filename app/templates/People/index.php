<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Development $development
 * @var iterable<\App\Model\Entity\Person> $people
 * @var bool $canManage
 */
$this->assign('title', 'Personas');
$this->loadHelper('Paginator');
$base = '/developments/' . (int)$development->id . '/people';
?>
<p><a href="<?= h($this->Url->build('/developments')) ?>">&larr; Desarrollos</a></p>
<h1>Personas de <?= h($development->name) ?></h1>
<?php if ($canManage) : ?>
<p><a href="<?= h($this->Url->build($base . '/add')) ?>">Nueva persona</a></p>
<?php endif; ?>
<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Correo electrónico</th>
            <th>Teléfono</th>
            <th>RFC</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($people as $person) : ?>
        <tr>
            <td><?= h($person->name) ?></td>
            <td><?= h($person->email) ?></td>
            <td><?= h($person->phone) ?></td>
            <td><?= h($person->rfc) ?></td>
            <td>
                <?php if ($canManage) : ?>
                    <a href="<?= h($this->Url->build($base . '/' . (int)$person->id . '/edit')) ?>">Editar</a>
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
