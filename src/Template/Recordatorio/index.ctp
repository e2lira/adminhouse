<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Recordatorio[]|\Cake\Collection\CollectionInterface $recordatorio
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Recordatorio'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="recordatorio index large-9 medium-8 columns content">
    <h3><?= __('Recordatorio') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_recordatorio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('destinatarios') ?></th>
                <th scope="col"><?= $this->Paginator->sort('asunto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('adjunto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('dir') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recordatorio as $recordatorio): ?>
            <tr>
                <td><?= $this->Number->format($recordatorio->id_recordatorio) ?></td>
                <td><?= h($recordatorio->destinatarios) ?></td>
                <td><?= h($recordatorio->asunto) ?></td>
                <td><?= h($recordatorio->adjunto) ?></td>
                <td><?= h($recordatorio->dir) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $recordatorio->id_recordatorio]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $recordatorio->id_recordatorio]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $recordatorio->id_recordatorio], ['confirm' => __('Are you sure you want to delete # {0}?', $recordatorio->id_recordatorio)]) ?>
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
