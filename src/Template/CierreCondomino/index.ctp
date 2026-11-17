<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CierreCondomino[]|\Cake\Collection\CollectionInterface $cierreCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Cierre Condomino'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="cierreCondomino index large-9 medium-8 columns content">
    <h3><?= __('Cierre Condomino') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_cierre') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condomino') ?></th>
                <th scope="col"><?= $this->Paginator->sort('saldo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cierreCondomino as $cierreCondomino): ?>
            <tr>
                <td><?= $this->Number->format($cierreCondomino->id_cierre) ?></td>
                <td><?= $this->Number->format($cierreCondomino->id_condomino) ?></td>
                <td><?= h($cierreCondomino->saldo) ?></td>
                <td><?= h($cierreCondomino->fecha) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $cierreCondomino->id_cierre]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $cierreCondomino->id_cierre]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $cierreCondomino->id_cierre], ['confirm' => __('Are you sure you want to delete # {0}?', $cierreCondomino->id_cierre)]) ?>
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
