<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Favor $favor
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Favor'), ['action' => 'edit', $favor->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Favor'), ['action' => 'delete', $favor->id], ['confirm' => __('Are you sure you want to delete # {0}?', $favor->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Favor'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Favor'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="favor view large-9 medium-8 columns content">
    <h3><?= h($favor->id) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($favor->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Pago') ?></th>
            <td><?= $this->Number->format($favor->id_pago) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($favor->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($favor->id_condomino) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Abono') ?></th>
            <td><?= $this->Number->format($favor->abono) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($favor->fecha) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Estatus') ?></th>
            <td><?= $favor->estatus ? __('Yes') : __('No'); ?></td>
        </tr>
    </table>
</div>
