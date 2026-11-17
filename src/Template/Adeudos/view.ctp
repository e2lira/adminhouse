<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Adeudo $adeudo
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Adeudo'), ['action' => 'edit', $adeudo->id_adeudo]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Adeudo'), ['action' => 'delete', $adeudo->id_adeudo], ['confirm' => __('Are you sure you want to delete # {0}?', $adeudo->id_adeudo)]) ?> </li>
        <li><?= $this->Html->link(__('List Adeudos'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Adeudo'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="adeudos view large-9 medium-8 columns content">
    <h3><?= h($adeudo->id_adeudo) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Concepto') ?></th>
            <td><?= h($adeudo->concepto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Adeudo') ?></th>
            <td><?= $this->Number->format($adeudo->id_adeudo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($adeudo->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($adeudo->id_condomino) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Monto') ?></th>
            <td><?= $this->Number->format($adeudo->monto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Mes') ?></th>
            <td><?= h($adeudo->mes) ?></td>
        </tr>
    </table>
</div>
