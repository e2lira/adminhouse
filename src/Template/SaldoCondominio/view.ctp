<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCondominio $saldoCondominio
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Saldo Condominio'), ['action' => 'edit', $saldoCondominio->id_saldo]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Saldo Condominio'), ['action' => 'delete', $saldoCondominio->id_saldo], ['confirm' => __('Are you sure you want to delete # {0}?', $saldoCondominio->id_saldo)]) ?> </li>
        <li><?= $this->Html->link(__('List Saldo Condominio'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Saldo Condominio'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="saldoCondominio view large-9 medium-8 columns content">
    <h3><?= h($saldoCondominio->id_saldo) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Saldo') ?></th>
            <td><?= $this->Number->format($saldoCondominio->id_saldo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($saldoCondominio->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Saldo') ?></th>
            <td><?= $this->Number->format($saldoCondominio->saldo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($saldoCondominio->fecha) ?></td>
        </tr>
    </table>
</div>
