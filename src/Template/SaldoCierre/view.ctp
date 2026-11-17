<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCierre $saldoCierre
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Saldo Cierre'), ['action' => 'edit', $saldoCierre->id_cierre]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Saldo Cierre'), ['action' => 'delete', $saldoCierre->id_cierre], ['confirm' => __('Are you sure you want to delete # {0}?', $saldoCierre->id_cierre)]) ?> </li>
        <li><?= $this->Html->link(__('List Saldo Cierre'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Saldo Cierre'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="saldoCierre view large-9 medium-8 columns content">
    <h3><?= h($saldoCierre->id_cierre) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Cierre') ?></th>
            <td><?= $this->Number->format($saldoCierre->id_cierre) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($saldoCierre->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Saldo') ?></th>
            <td><?= $this->Number->format($saldoCierre->saldo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($saldoCierre->fecha) ?></td>
        </tr>
    </table>
</div>
