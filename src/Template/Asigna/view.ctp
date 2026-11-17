<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asigna $asigna
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Asigna'), ['action' => 'edit', $asigna->id_asigna]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Asigna'), ['action' => 'delete', $asigna->id_asigna], ['confirm' => __('Are you sure you want to delete # {0}?', $asigna->id_asigna)]) ?> </li>
        <li><?= $this->Html->link(__('List Asigna'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Asigna'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="asigna view large-9 medium-8 columns content">
    <h3><?= h($asigna->id_asigna) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Asigna') ?></th>
            <td><?= $this->Number->format($asigna->id_asigna) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id User') ?></th>
            <td><?= $this->Number->format($asigna->id_user) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($asigna->id_condomino) ?></td>
        </tr>
    </table>
</div>
