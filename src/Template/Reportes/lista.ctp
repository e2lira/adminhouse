<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RecibosPago[]|\Cake\Collection\CollectionInterface $recibosPago
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
                    <li><?= $this->Html->link(__('Lista de Condominios'), ['controller' => 'Condominio', 'action' =>
                            'index']) ?>
                    </li>
                    <li><?= $this->Html->link(__('Nuevo Reporte'), ['controller' => 'Reportes', 'action' => 'nuevo',
                            'id'=>$id_condominio]) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <br><br>
<div class="col-xs-12 col-sm-12 col-md-9 ">
    <div class="page-header">
        <h2><i class="fa fa-wpforms"></i> Recibos generados por mes</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover  table-sm" id="table">
            <thead>
        <tr>
            <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
            <th scope="col"><?= $this->Paginator->sort('fecha_creacion') ?></th>
            <th scope="col" class="actions"><?= __('Acciones') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($recibosPago as $recibosPago): ?>
            <tr>
                <td><?= $this->Time->format($recibosPago['fecha'],'MMMM Y') ?></td>
                <td><?= $this->Time->format($recibosPago['fecha_creacion'],'d MMMM Y') ?></td>

                <td class="actions">
                    <?= $this->Html->link(__('Ver reporte'), ['action' => 'recibos', $recibosPago->id_reporte]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $recibosPago->id_reporte], ['confirm' => __('Are you sure you want to delete # {0}?', $recibosPago->id_reporte)]) ?>
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
