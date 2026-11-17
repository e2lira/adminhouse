<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCierre[]|\Cake\Collection\CollectionInterface $saldoCierre
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Saldo Cierre'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="saldoCierre index large-9 medium-8 columns content">
    <h3><?= __('Saldo Cierre') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_cierre') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condominio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('saldo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($saldoCierre as $saldoCierre): ?>
            <tr>
                <td><?= $this->Number->format($saldoCierre->id_cierre) ?></td>
                <td><?= $this->Number->format($saldoCierre->id_condominio) ?></td>
                <td><?= $this->Number->format($saldoCierre->saldo) ?></td>
                <td><?= h($saldoCierre->fecha) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $saldoCierre->id_cierre]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $saldoCierre->id_cierre]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $saldoCierre->id_cierre], ['confirm' => __('Are you sure you want to delete # {0}?', $saldoCierre->id_cierre)]) ?>
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
