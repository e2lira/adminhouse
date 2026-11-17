<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoPago $tipoPago
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Tipo Pago'), ['action' => 'edit', $tipoPago->id_tipo]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Tipo Pago'), ['action' => 'delete', $tipoPago->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoPago->id_tipo)]) ?> </li>
        <li><?= $this->Html->link(__('List Tipo Pago'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Tipo Pago'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="tipoPago view large-9 medium-8 columns content">
    <h3><?= h($tipoPago->id_tipo) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Nombre') ?></th>
            <td><?= h($tipoPago->nombre) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Tipo') ?></th>
            <td><?= $this->Number->format($tipoPago->id_tipo) ?></td>
        </tr>
    </table>
</div>
