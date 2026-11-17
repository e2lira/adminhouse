<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CierreCondomino $cierreCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Cierre Condomino'), ['action' => 'edit', $cierreCondomino->id_cierre]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Cierre Condomino'), ['action' => 'delete', $cierreCondomino->id_cierre], ['confirm' => __('Are you sure you want to delete # {0}?', $cierreCondomino->id_cierre)]) ?> </li>
        <li><?= $this->Html->link(__('List Cierre Condomino'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Cierre Condomino'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="cierreCondomino view large-9 medium-8 columns content">
    <h3><?= h($cierreCondomino->id_cierre) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Saldo') ?></th>
            <td><?= h($cierreCondomino->saldo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($cierreCondomino->fecha) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Cierre') ?></th>
            <td><?= $this->Number->format($cierreCondomino->id_cierre) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($cierreCondomino->id_condomino) ?></td>
        </tr>
    </table>
</div>
