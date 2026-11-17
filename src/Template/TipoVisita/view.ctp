<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoVisitum $tipoVisitum
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Tipo Visitum'), ['action' => 'edit', $tipoVisitum->id_tipo]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Tipo Visitum'), ['action' => 'delete', $tipoVisitum->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoVisitum->id_tipo)]) ?> </li>
        <li><?= $this->Html->link(__('List Tipo Visita'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Tipo Visitum'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="tipoVisita view large-9 medium-8 columns content">
    <h3><?= h($tipoVisitum->id_tipo) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Tipo Visita') ?></th>
            <td><?= h($tipoVisitum->tipo_visita) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Tipo') ?></th>
            <td><?= $this->Number->format($tipoVisitum->id_tipo) ?></td>
        </tr>
    </table>
</div>
