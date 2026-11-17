<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bloque $bloque
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Bloque'), ['action' => 'edit', $bloque->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Bloque'), ['action' => 'delete', $bloque->id], ['confirm' => __('Are you sure you want to delete # {0}?', $bloque->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Bloque'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Bloque'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="bloque view large-9 medium-8 columns content">
    <h3><?= h($bloque->id) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Cuota Letra') ?></th>
            <td><?= h($bloque->cuota_letra) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Concepto') ?></th>
            <td><?= h($bloque->concepto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($bloque->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($bloque->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($bloque->id_condomino) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Tipo') ?></th>
            <td><?= $this->Number->format($bloque->id_tipo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cuota') ?></th>
            <td><?= $this->Number->format($bloque->cuota) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Cargo') ?></th>
            <td><?= h($bloque->fecha_cargo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($bloque->fecha) ?></td>
        </tr>
    </table>
</div>
