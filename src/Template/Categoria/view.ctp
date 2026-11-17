<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Categorium $categorium
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Categorium'), ['action' => 'edit', $categorium->id_categoria]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Categorium'), ['action' => 'delete', $categorium->id_categoria], ['confirm' => __('Are you sure you want to delete # {0}?', $categorium->id_categoria)]) ?> </li>
        <li><?= $this->Html->link(__('List Categoria'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Categorium'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="categoria view large-9 medium-8 columns content">
    <h3><?= h($categorium->id_categoria) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Categoria') ?></th>
            <td><?= h($categorium->categoria) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Categoria') ?></th>
            <td><?= $this->Number->format($categorium->id_categoria) ?></td>
        </tr>
    </table>
</div>
