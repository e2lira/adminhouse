<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Excel[]|\Cake\Collection\CollectionInterface $excel
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Excel'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="excel index large-9 medium-8 columns content">
    <h3><?= __('Excel') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_excel') ?></th>
                <th scope="col"><?= $this->Paginator->sort('dir') ?></th>
                <th scope="col"><?= $this->Paginator->sort('archivo') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($excel as $excel): ?>
            <tr>
                <td><?= $this->Number->format($excel->id_excel) ?></td>
                <td><?= h($excel->dir) ?></td>
                <td><?= h($excel->archivo) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $excel->id_excel]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $excel->id_excel]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $excel->id_excel], ['confirm' => __('Are you sure you want to delete # {0}?', $excel->id_excel)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
    </div>
</div>
