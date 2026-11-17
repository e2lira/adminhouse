<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condominio[]|\Cake\Collection\CollectionInterface $condominio
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
                    <li><?= $this->Html->link(__('New Condominio'), ['action' => 'add']) ?></li>
                </ul>
            </div>
        </div>
        <br>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter">
            Ayuda
        </button>
    </div>
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalCenterTitle">Descripción de los iconos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="font-size: 20px; ">
                    <i class='fa fa-eye'></i> <label>Ver</label><br><br>
                    <i class='fa fa-edit'></i> <label>Edit</label><br><br>
                    <i class='fa fa-address-book'></i> <label>Condomino</label><br><br>
                    <i class='fa fa-credit-card'></i> <label>Pago</label><br><br>
                    <i class='fa fa-dollar'></i> <label>Gasto</label><br><br>
                    <i class='fa fa-wpforms'></i> <label>Cuota</label><br><br>
                    <i class='fa fa-clipboard'></i> <label>Bitacora</label><br><br>
                    <i class='fa fa-trash'></i> <label>Borrar</label>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-building"></i> Condominio</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col"><?= $this->Paginator->sort('nombre_condominio') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('direccion_condominio') ?></th>
                    <th scope="col" class="actions"><?= __('Actions') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($condominio as $condominio): ?>
                <tr>
                    <td><?= h($condominio->nombre_condominio) ?></td>
                    <td><?= h($condominio->direccion_condominio) ?></td>
                    <td class="actions">
                    <span class="" tabindex="0" data-toggle="tooltip" title="Ver">
                     <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Editar">
                     <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Condomino">
                    <?= $this->Html->link("<i class='fa fa-address-book'></i>", ['controller' => 'Condomino', 'action' => 'index', 'id'=>$condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Pagos">
                    <?= $this->Html->link("<i class='fa fa-credit-card'></i>", ['controller' => 'Pago', 'action' => 'add', 'id'=>$condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Gastos">
                    <?= $this->Html->link("<i class='fa fa-dollar'></i>", ['controller' => 'Gasto', 'action' => 'add', 'id'=>$condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Cuota">
                    <?= $this->Html->link("<i class='fa fa-wpforms'></i>", ['controller' => 'Cuotas', 'action' => 'add', 'id'=>$condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Reportes">
                    <?= $this->Html->link("<i class='fa fa-clipboard'></i>", ['controller' => 'Gasto', 'action' => 'add', 'id'=>$condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Folios">
                    <?= $this->Html->link("<i class='fa fa-expeditedssl'></i>", ['controller' => 'Folios', 'action' => 'add', 'id'=>$condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Proveedores">
                    <?= $this->Html->link("<i class='fa fa-truck'></i>", ['controller' => 'Proveedores', 'action' => 'add', 'id'=>$condominio['id_condominio']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Borrar">
                    <button type="button" class="btn btn-xs" id="delete" name="delete" data-id-condominio="<?= $condominio['id_condominio'] ?>" data-nombre-condominio="<?= $condominio['nombre_condominio'] ?>"><i class='fa fa-trash'></i></button>
                    </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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

<!-- Modal -->
<div class="modal fade" id="modalUsers" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desea usted eliminar el condominio?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button id="borrar" type="button" class="btn btn-danger" >Eliminar</button>
            </div>
        </div>

    </div>
</div>

<script>
    $(document).ready(function(){
        console.log('Ya estoy JQuery');
        $('#tableUsers button[type="button"]').each(function (index, element) { //index: N. objetos dentro del table, element: objeto que se esta buscando
            console.log("Row ..........");
            $(element).on('click', function () {
                var button = $(this);
                $('#modalUsers').on('show.bs.modal', function (event) {
                    var idCon = button.attr('data-id-condominio');
                    console.log('Condominio' + idCon);
                    $(event.currentTarget).find('button[id="borrar"]').attr('data-id', idCon);
                    $('#modal').data('data-id', idCon);
                    var modal  = $(this);
                    modal.find('.modal-body').html(button.data('nombre-condominio'));
                });
                $('#modalUsers').modal('show');
            });
        });
    });


    $('#borrar').on('click', function () {
        var id = $(this).attr('data-id');
        console.log('Borrar');
        $.ajax({
            method: "get",
            url: '/condominio/borrar/' + id,
            dataType: 'json',
            success: function(msg) {
                result = msg[0];
                console.log(result.message);
                if (result.result === 'ok') {
                    delete(id);
                    window.location.reload();
                }
            }
        });
    });

    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
<style>
    label{
        color: #843534;
    }
</style>
