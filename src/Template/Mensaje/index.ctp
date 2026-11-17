<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mensaje[]|\Cake\Collection\CollectionInterface $mensaje
 */
?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Regresar al menú'), ['controller' => 'Condominio', 'action' => 'index']) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-address-book"></i> Mensajes</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tableCond">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('titulo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condominio',['label'=>'Condominio']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condomino',['label'=>'Condomino']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('estatus') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mensaje as $mensaje): ?>
            <tr>
                <td><?= h($mensaje['titulo']) ?></td>
                <td><?= $this->Time->format($mensaje['fecha'],'d MMMM Y') ?></td>
                <td><?= h($mensaje['condominio']) ?></td>
                <td><?= h($mensaje['depto']) ?></td>
                <?php   if($mensaje['estatus'] == 0){
                    echo "<td><img src='../../../img/verde.png' style='width: 20px;'></td>";
                }else{
                    echo "<td><img src='../../../img/rojo.png' style='width: 20px;'></td>";
                }

                ?>
                <td class="actions">
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                    <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $mensaje['id_mensaje']], ['escape' => false]) ?>
                    </span>

                    <?= $this->Form->postLink(__("<i class='fa fa-trash'></i>"), ['action' => 'delete', $mensaje['id_mensaje']], ['escape' => false], ['confirm' => __('Are you sure you want to delete # {0}?', $mensaje['id_mensaje'])]) ?>
                    <?= $this->Form->postLink(__("<i class='fa fa-envelope-open'></i>"), [ 'action' => 'leido', $mensaje['id_mensaje']],['escape' => false], ['confirm' => __('Seguro que quieres marcar como leído el mensaje {0}?', $mensaje['titulo'])]) ?>
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
<style>
    .circulo {
        width: 1%;
        height: 1%;
        border-radius: 50%;
        background: #5cb85c;
    }
</style>
