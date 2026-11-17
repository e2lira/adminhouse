<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Supervision[]|\Cake\Collection\CollectionInterface $supervision
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
                    <li><i class="fa fa-caret-left" style="color: #0056b3;"></i> <?= $this->Html->link(__('Volver'), ['controller' => 'Condominio', 'action' =>
                            'index']) ?>
                    </li>
                    <li><i class="fa fa-plus" style="color: #0056b3;"></i> <?= $this->Html->link(__('Nuevo reporte'), ['controller' => 'Supervision', 'action' => 'add',
                            'id'=>$id_condominio]) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <br><br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-book"></i> Reportes de supervision</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_supervision') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condominio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($supervision as $supervision): ?>
            <tr>
                <td><?= $this->Number->format($supervision->id_supervision) ?></td>
                <td><?= $this->Number->format($supervision->id_condominio) ?></td>
                <td><?= h($supervision->fecha) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Reporte'), ['action' => 'view', $supervision->id_supervision]) ?>
                    <?= $this->Html->link(__('Añadir'), ['action' => 'areas', $supervision->id_supervision]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $supervision->id_supervision]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $supervision->id_supervision], ['confirm' => __('Are you sure you want to delete # {0}?', $supervision->id_supervision)]) ?>
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
