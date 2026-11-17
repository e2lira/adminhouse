<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoCuotum $tipoCuotum
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Tipo Cuotum'), ['action' => 'edit', $tipoCuotum->id_tipo]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Tipo Cuotum'), ['action' => 'delete', $tipoCuotum->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoCuotum->id_tipo)]) ?> </li>
        <li><?= $this->Html->link(__('List Tipo Cuota'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Tipo Cuotum'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="tipoCuota view large-9 medium-8 columns content">
    <h3><?= h($tipoCuotum->id_tipo) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Tipo Cuota') ?></th>
            <td><?= h($tipoCuotum->tipo_cuota) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Tipo') ?></th>
            <td><?= $this->Number->format($tipoCuotum->id_tipo) ?></td>
        </tr>
    </table>
</div>
