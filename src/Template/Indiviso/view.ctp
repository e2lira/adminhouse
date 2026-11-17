<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Indiviso $indiviso
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Indiviso'), ['action' => 'edit', $indiviso->id_cuotas]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Indiviso'), ['action' => 'delete', $indiviso->id_cuotas], ['confirm' => __('Are you sure you want to delete # {0}?', $indiviso->id_cuotas)]) ?> </li>
        <li><?= $this->Html->link(__('List Indiviso'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Indiviso'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="indiviso view large-9 medium-8 columns content">
    <h3><?= h($indiviso->id_cuotas) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= h($indiviso->id_condomino) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cuota Letra') ?></th>
            <td><?= h($indiviso->cuota_letra) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Concepto') ?></th>
            <td><?= h($indiviso->concepto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Cuotas') ?></th>
            <td><?= $this->Number->format($indiviso->id_cuotas) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($indiviso->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Tipo') ?></th>
            <td><?= $this->Number->format($indiviso->id_tipo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cuota') ?></th>
            <td><?= $this->Number->format($indiviso->cuota) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Cargo') ?></th>
            <td><?= h($indiviso->fecha_cargo) ?></td>
        </tr>
    </table>
</div>
