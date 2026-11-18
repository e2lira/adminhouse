<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Cake\Datasource\EntityInterface> $developments
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
        </tr>
    </thead>
    <tbody>
        <?php foreach ($developments as $development) : ?>
        <tr>
            <td><?= h($development->name) ?></td>
            <td><?= h($types[$development->type] ?? $development->type) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
