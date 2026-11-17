<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoPago[]|\Cake\Collection\CollectionInterface $tipoPago
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
                    <li><?= $this->Html->link(__('Nuevo Tipo Pago'), ['action' => 'add', 'id'=>$id_condominio]) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <br><br>

    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-dollar"></i> Tipo de Pago</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tablePro">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('nombre') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tipoPago as $tipoPago): ?>
            <tr>
                <td><?= h($tipoPago->nombre) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $tipoPago->id_tipo]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $tipoPago->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoPago->id_tipo)]) ?>
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
</div>
