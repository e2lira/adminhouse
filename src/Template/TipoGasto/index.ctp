<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoGasto[]|\Cake\Collection\CollectionInterface $tipoGasto
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
                    <li><?= $this->Html->link(__('Nuevo Tipo Gasto'), ['action' => 'add', 'id'=>$id_condominio]) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <br><br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-credit-card"></i> Tipo de Gasto</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tablePro">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('tipo_gasto') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tipoGasto as $tipoGasto): ?>
            <tr>
                <td><?= h($tipoGasto->tipo_gasto) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $tipoGasto->id_tipo]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $tipoGasto->id_tipo], ['confirm' => __('Are you sure you want to delete # {0}?', $tipoGasto->id_tipo)]) ?>
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
