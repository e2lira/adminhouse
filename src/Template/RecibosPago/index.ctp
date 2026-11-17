<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RecibosPago[]|\Cake\Collection\CollectionInterface $recibosPago
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Recibos Pago'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="recibosPago index large-9 medium-8 columns content">
    <h3><?= __('Recibos Pago') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_reporte') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condominio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha_creacion') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recibosPago as $recibosPago): ?>
            <tr>
                <td><?= $this->Number->format($recibosPago->id_reporte) ?></td>
                <td><?= $this->Number->format($recibosPago->id_condominio) ?></td>
                <td><?= h($recibosPago->fecha) ?></td>
                <td><?= h($recibosPago->fecha_creacion) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $recibosPago->id_reporte]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $recibosPago->id_reporte]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $recibosPago->id_reporte], ['confirm' => __('Are you sure you want to delete # {0}?', $recibosPago->id_reporte)]) ?>
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
