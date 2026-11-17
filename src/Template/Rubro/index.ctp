<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Rubro[]|\Cake\Collection\CollectionInterface $rubro
 */
?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 ">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('New Rubro'), ['action' => 'add']) ?></li>
                </ul>
            </div>
        </div>
        <br>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-address-book"></i> Condomino</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tableCond">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('rubro') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rubro as $rubro): ?>
            <tr>
                <td><?= h($rubro->rubro) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $rubro->id_rubro]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $rubro->id_rubro]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $rubro->id_rubro], ['confirm' => __('Are you sure you want to delete # {0}?', $rubro->id_rubro)]) ?>
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
</div>
