<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCondomino $saldoCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Saldo Condomino'), ['action' => 'edit', $saldoCondomino->id_saldo]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Saldo Condomino'), ['action' => 'delete', $saldoCondomino->id_saldo], ['confirm' => __('Are you sure you want to delete # {0}?', $saldoCondomino->id_saldo)]) ?> </li>
        <li><?= $this->Html->link(__('List Saldo Condomino'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Saldo Condomino'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="saldoCondomino view large-9 medium-8 columns content">
    <h3><?= h($saldoCondomino->id_saldo) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Saldo') ?></th>
            <td><?= h($saldoCondomino->saldo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Saldo') ?></th>
            <td><?= $this->Number->format($saldoCondomino->id_saldo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($saldoCondomino->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($saldoCondomino->id_condomino) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($saldoCondomino->fecha) ?></td>
        </tr>
    </table>
</div>
