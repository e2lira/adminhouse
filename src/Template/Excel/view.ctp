<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Excel $excel
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Excel'), ['action' => 'edit', $excel->id_excel]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Excel'), ['action' => 'delete', $excel->id_excel], ['confirm' => __('Are you sure you want to delete # {0}?', $excel->id_excel)]) ?> </li>
        <li><?= $this->Html->link(__('List Excel'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Excel'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="excel view large-9 medium-8 columns content">
    <h3><?= h($excel->id_excel) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Dir') ?></th>
            <td><?= h($excel->dir) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Archivo') ?></th>
            <td><?= h($excel->archivo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Excel') ?></th>
            <td><?= $this->Number->format($excel->id_excel) ?></td>
        </tr>
    </table>
</div>
