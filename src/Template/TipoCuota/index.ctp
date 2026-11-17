<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoCuotum[]|\Cake\Collection\CollectionInterface $tipoCuota
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
                    <li><?= $this->Html->link(__('Nuevo Tipo Cuota'), ['action' => 'add', 'id'=>$id_condominio]) ?></li>

                </ul>
            </div>
        </div>
    </div>
    <br><br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-wpforms"></i> Tipo Cuota</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_tipo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('tipo_cuota') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tipoCuota as $tipoCuotum): ?>
            <tr>
                <td><?= $this->Number->format($tipoCuotum->id_tipo) ?></td>
                <td><?= h($tipoCuotum->tipo_cuota) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $tipoCuotum->id_tipo]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $tipoCuotum->id_tipo]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $tipoCuotum->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoCuotum->id_tipo)]) ?>
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
