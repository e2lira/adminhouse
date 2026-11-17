<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Pendiente $pendiente
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Pendiente'), ['action' => 'edit', $pendiente->id_pendientes]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Pendiente'), ['action' => 'delete', $pendiente->id_pendientes], ['confirm' => __('Are you sure you want to delete # {0}?', $pendiente->id_pendientes)]) ?> </li>
        <li><?= $this->Html->link(__('List Pendientes'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Pendiente'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="pendientes view large-9 medium-8 columns content">
    <h3><?= h($pendiente->id_pendientes) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Contenido') ?></th>
            <td><?= h($pendiente->contenido) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Pendientes') ?></th>
            <td><?= $this->Number->format($pendiente->id_pendientes) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($pendiente->fecha) ?></td>
        </tr>
    </table>
</div>
