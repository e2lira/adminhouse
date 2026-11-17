<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoGasto $tipoGasto
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Tipo Gasto'), ['action' => 'edit', $tipoGasto->id_tipo]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Tipo Gasto'), ['action' => 'delete', $tipoGasto->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoGasto->id_tipo)]) ?> </li>
        <li><?= $this->Html->link(__('List Tipo Gasto'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Tipo Gasto'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="tipoGasto view large-9 medium-8 columns content">
    <h3><?= h($tipoGasto->id_tipo) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Tipo Gasto') ?></th>
            <td><?= h($tipoGasto->tipo_gasto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Tipo') ?></th>
            <td><?= $this->Number->format($tipoGasto->id_tipo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Monto') ?></th>
            <td><?= $this->Number->format($tipoGasto->monto) ?></td>
        </tr>
    </table>
</div>
