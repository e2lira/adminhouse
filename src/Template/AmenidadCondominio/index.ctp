<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AmenidadCondominio[]|\Cake\Collection\CollectionInterface $amenidadCondominio
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Amenidad Condominio'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="amenidadCondominio index large-9 medium-8 columns content">
    <h3><?= __('Amenidad Condominio') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_amenidad') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condominio') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($amenidadCondominio as $amenidadCondominio): ?>
            <tr>
                <td><?= $this->Number->format($amenidadCondominio->id) ?></td>
                <td><?= $this->Number->format($amenidadCondominio->id_amenidad) ?></td>
                <td><?= h($amenidadCondominio->id_condominio) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $amenidadCondominio->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $amenidadCondominio->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $amenidadCondominio->id], ['confirm' => __('Are you sure you want to delete # {0}?', $amenidadCondominio->id)]) ?>
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
