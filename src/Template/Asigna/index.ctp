<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asigna[]|\Cake\Collection\CollectionInterface $asigna
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Asigna'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="asigna index large-9 medium-8 columns content">
    <h3><?= __('Asigna') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
        <tr>
            <th scope="col"><?= $this->Paginator->sort('id_asigna') ?></th>
            <th scope="col"><?= $this->Paginator->sort('id_user') ?></th>
            <th scope="col"><?= $this->Paginator->sort('id_condomino') ?></th>
            <th scope="col" class="actions"><?= __('Actions') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($asigna as $asigna): ?>
            <tr>
                <td><?= $this->Number->format($asigna->id_asigna) ?></td>
                <td><?= $this->Number->format($asigna->id_user) ?></td>
                <td><?= $this->Number->format($asigna->id_condomino) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $asigna->id_asigna]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $asigna->id_asigna]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $asigna->id_asigna], ['confirm' => __('Are you sure you want to delete # {0}?', $asigna->id_asigna)]) ?>
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
