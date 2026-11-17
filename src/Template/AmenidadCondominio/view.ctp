<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AmenidadCondominio $amenidadCondominio
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Amenidad Condominio'), ['action' => 'edit', $amenidadCondominio->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Amenidad Condominio'), ['action' => 'delete', $amenidadCondominio->id], ['confirm' => __('Are you sure you want to delete # {0}?', $amenidadCondominio->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Amenidad Condominio'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Amenidad Condominio'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="amenidadCondominio view large-9 medium-8 columns content">
    <h3><?= h($amenidadCondominio->id) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= h($amenidadCondominio->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($amenidadCondominio->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Amenidad') ?></th>
            <td><?= $this->Number->format($amenidadCondominio->id_amenidad) ?></td>
        </tr>
    </table>
</div>
