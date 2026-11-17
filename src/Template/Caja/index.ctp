<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Caja[]|\Cake\Collection\CollectionInterface $caja
 */
?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 ">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul class="side-nav">
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['controller' => 'Condominio', 'action' =>
                            'index']) ?>
                    </li>
                    <br>
                    <li style="background-color: #c4e3f3; color: white;"><?= $this->Html->link(__('Caja chica'), ['controller' => 'caja', 'action' => 'add', 'id'=>$condominio['id_condominio']]) ?></li>

                </ul>
            </div>
        </div>
    </div>
<div class="col-xs-12 col-sm-12 col-md-9 ">
    <h2><i class="fa fa-dollar"></i> Gastos Caja Chica</h2>
    <div class="table-responsive">
        <table class="table table-striped table-hover  table-sm" id="table">
            <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_tipo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($caja as $caja): ?>
            <tr>
                <td><?= h($caja['tipo']) ?></td>
                <td><?= $this->Time->format($caja['fecha'],'d MMMM Y') ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Conceptos'), ['controller'=>'conceptos','action' => 'index', $caja['id_gasto']]) ?>
                    <?= $this->Html->link(__('Poliza'), ['action' => 'view', $caja['id_gasto']]) ?>
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $caja['id_gasto']]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $caja['id_gasto']], ['confirm' => __('Are you sure you want to delete # {0}?', $caja['id_gasto'])]) ?>
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
