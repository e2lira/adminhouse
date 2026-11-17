<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Rubro $rubro
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Rubro'), ['action' => 'edit', $rubro->id_rubro]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Rubro'), ['action' => 'delete', $rubro->id_rubro], ['confirm' => __('Are you sure you want to delete # {0}?', $rubro->id_rubro)]) ?> </li>
        <li><?= $this->Html->link(__('List Rubro'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Rubro'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="rubro view large-9 medium-8 columns content">
    <h3><?= h($rubro->id_rubro) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Rubro') ?></th>
            <td><?= h($rubro->rubro) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Rubro') ?></th>
            <td><?= $this->Number->format($rubro->id_rubro) ?></td>
        </tr>
    </table>
</div>
