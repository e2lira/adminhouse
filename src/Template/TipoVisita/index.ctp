<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoVisitum[]|\Cake\Collection\CollectionInterface $tipoVisita
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Tipo Visitum'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="tipoVisita index large-9 medium-8 columns content">
    <h3><?= __('Tipo Visita') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_tipo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('tipo_visita') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tipoVisita as $tipoVisitum): ?>
            <tr>
                <td><?= $this->Number->format($tipoVisitum->id_tipo) ?></td>
                <td><?= h($tipoVisitum->tipo_visita) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $tipoVisitum->id_tipo]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $tipoVisitum->id_tipo]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $tipoVisitum->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoVisitum->id_tipo)]) ?>
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
