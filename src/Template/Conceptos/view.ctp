<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Concepto $concepto
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Concepto'), ['action' => 'edit', $concepto->id_conceptos]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Concepto'), ['action' => 'delete', $concepto->id_conceptos], ['confirm' => __('Are you sure you want to delete # {0}?', $concepto->id_conceptos)]) ?> </li>
        <li><?= $this->Html->link(__('List Conceptos'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Concepto'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="conceptos view large-9 medium-8 columns content">
    <h3><?= h($concepto->id_conceptos) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Concepto') ?></th>
            <td><?= h($concepto->concepto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Gasto') ?></th>
            <td><?= h($concepto->gasto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Monto Letra') ?></th>
            <td><?= h($concepto->monto_letra) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Conceptos') ?></th>
            <td><?= $this->Number->format($concepto->id_conceptos) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Gasto') ?></th>
            <td><?= $this->Number->format($concepto->id_gasto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($concepto->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($concepto->fecha) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Pago') ?></th>
            <td><?= h($concepto->fecha_pago) ?></td>
        </tr>
    </table>
</div>
