<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Area $area
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Area'), ['action' => 'edit', $area->id_area]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Area'), ['action' => 'delete', $area->id_area], ['confirm' => __('Are you sure you want to delete # {0}?', $area->id_area)]) ?> </li>
        <li><?= $this->Html->link(__('List Areas'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Area'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="areas view large-9 medium-8 columns content">
    <h3><?= h($area->id_area) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Concepto') ?></th>
            <td><?= h($area->concepto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Estado') ?></th>
            <td><?= h($area->estado) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen') ?></th>
            <td><?= h($area->imagen) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Url') ?></th>
            <td><?= h($area->url) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Observaciones') ?></th>
            <td><?= h($area->observaciones) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Area') ?></th>
            <td><?= $this->Number->format($area->id_area) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Supervision') ?></th>
            <td><?= $this->Number->format($area->id_supervision) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($area->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($area->fecha) ?></td>
        </tr>
    </table>
</div>
