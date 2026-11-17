<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condomino[]|\Cake\Collection\CollectionInterface $condomino
 */
?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
        <div class="card">
            <div class="container">
                <h2 style="text-shadow: 2px 2px 2px white !important;">Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Nuevo Condomino'), ['controller' => 'Condomino', 'action' => 'add',
                        'id'=>$id_condominio]) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-address-book"></i> Condomino</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tableCond">
                <thead>
                <tr>
                    <th scope="col"><?= $this->Paginator->sort('numero_departamento', ['Departamento']) ?></th>
                    <th scope="col"><?= $this->Paginator->sort('condominio') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('residente_condominio', ['Residente']) ?></th>
                    <th scope="col"><?= $this->Paginator->sort('correo_contacto', ['Correo']) ?></th>
                    <th scope="col"><?= $this->Paginator->sort('estatus', ['Estatus']) ?></th>
                    <th scope="col" class="actions"><?= __('Acciones') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($condomino as $condomino): ?>
                <tr>
                    <td><?= h($condomino['numero_departamento']) ?></td>
                    <td><?= h($condomino['condominio']) ?></td>
                    <td><?= h($condomino['residente_condominio']) ?></td>
                    <td><?= h($condomino['correo_contacto']) ?></td>
                    <?php
                    if($condomino['estatus']=='ACTIVO'){
                       echo  '<td style="color: #b2dba1;"><b>'.h($condomino['estatus']).'</b></td>';

                    }else{
                        echo  '<td style="color: #e4606d"><b>'.h($condomino['estatus']).'</b></td>';
                    }
                    ?>
                    <td class="actions">
                     <span class="" tabindex="0" data-toggle="tooltip" title="Asignar">
                     <?= $this->Html->link("<i class='fa fa-user'></i>", ['controller'=>'asigna','action' => 'add', $condomino['id_condomino']], ['escape' => false]) ?>
                    </span>
                    <span class="" tabindex="0" data-toggle="tooltip" title="saldo">
                     <?= $this->Html->link("<i class='fa fa-check'></i>", ['controller'=>'saldo_condomino','action' => 'add', $condomino['id_condomino'], $id_condominio], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Adeudos">
                        <?= $this->Html->link("<i class='fa fa-book'></i>", ['controller'=>'adeudos','action' => 'index', $condomino['id_condomino']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                    <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $condomino['id_condomino']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Editar">
                        <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $condomino['id_condomino']], ['escape' => false]) ?>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Borrar">
                        <button type="button" class="btn btn-xs" id="delete" name="delete"
                                data-id-condomino="<?= $condomino['id_condomino'] ?>"
                                data-numero-departamento="<?= $condomino['numero_departamento'] ?>"><i
                                class='fa fa-trash'></i></button>
                    </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Estatus">
                        <?= $this->Html->link("<i class='fa fa-adjust'></i>", ['action' => 'estatus', $condomino['id_condomino']], ['escape' => false]) ?>
                    </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="modalBorrar" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desea usted eliminar el condomino?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button id="borrar" type="button" class="btn btn-danger">Eliminar</button>
            </div>
        </div>

    </div>
</div>

<script>
    $(document).ready(function () {
        console.log('Ya estoy JQuery');
        $('#tableCond button[type="button"]').each(function (index, element) { //index: N. objetos dentro del table, element: objeto que se esta buscando
            console.log("Row ..........");
            $(element).on('click', function () {
                var button = $(this);
                $('#modalBorrar').on('show.bs.modal', function (event) {
                    var idMino = button.attr('data-id-condomino');
                    console.log('El condomino a borrar: ' + idMino);
                    $(event.currentTarget).find('button[id="borrar"]').attr('data-id', idMino);
                    $('#modal').data('data-id', idMino);
                    var modal = $(this);
                    modal.find('.modal-body').html(button.data('numero-departamento'));
                });
                $('#modalBorrar').modal('show');
            });
        });
    });

    $('#borrar').on('click', function () {
        var id = $(this).attr('data-id');
        console.log('Borrar');
        $.ajax({
            method: "get",
            url: '/condomino/borrar/' + id,
            dataType: 'json',
            success: function (msg) {
                result = msg[0];
                console.log(result.message);
                if (result.result === 'ok') {
                    delete (id);
                    $('#modalBorrado').modal('show');
                    $('#modalBorrar').modal('hide');
                    window.location.reload();
                }
            }
        });
    });

    $(document).ready(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
