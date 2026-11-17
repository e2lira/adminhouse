<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Proveedore[]|\Cake\Collection\CollectionInterface $proveedores
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
                    <li><?= $this->Html->link(__('List Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <br><br>

    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-truck"></i> Proveedores</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tablePro">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_condominio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('nom_proveedor') ?></th>
                <th scope="col"><?= $this->Paginator->sort('no_cuenta_proveedor') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proveedores as $proveedore): ?>
            <tr>
                <td><?= h($proveedore['condominio']) ?></td>
                <td><?= h($proveedore['nom_proveedor']) ?></td>
                <td><?= h($proveedore['no_cuenta_proveedor']) ?></td>
                <td class="actions">
                  <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                      <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $proveedore['id_proveedores']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Editar">
                        <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $proveedore['id_proveedores']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Borrar">
                        <button type="button" class="btn btn-xs" id="delete" name="delete"
                                data-id="<?= $proveedore['id_proveedores']?>" data-cuota="<?= $proveedore['nom_proveedor'] ?>"><i
                                class='fa fa-trash'></i></button>
                    </span>
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


<!-- Modal -->
<div class="modal fade" id="modalPro" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desea usted eliminar el proveedor?</h5>
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
        $('#tablePro button[type="button"]').each(function (index, element) { //index: N. objetos dentro del table, element: objeto que se esta buscando
            console.log("Row ..........");
            $(element).on('click', function () {
                var button = $(this);
                $('#modalPro').on('show.bs.modal', function (event) {
                    var idPro = button.attr('data-id');
                    console.log('pro' + idPro);
                    $(event.currentTarget).find('button[id="borrar"]').attr('data-id', idPro);
                    $('#modal').data('data-id', idPro);
                    var modal  = $(this);
                    modal.find('.modal-body').html(button.data('data-cuota'));
                });
                $('#modalPro').modal('show');
            });
        });
    });


    $('#borrar').on('click', function () {
        var id = $(this).attr('data-id');
        console.log('Borrar');
        $.ajax({
            method: "get",
            url: '/proveedores/borrar/' + id,
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


    $(document).ready(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
