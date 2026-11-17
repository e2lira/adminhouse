<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cuota[]|\Cake\Collection\CollectionInterface $cuotas
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
                    <li><?= $this->Html->link(__('Lista de  Condominios'), ['controller' => 'Condominio', 'action' =>
                        'index']) ?>
                    </li>
                    <br>
                    <?= $this->Form->create(null, ['url'=> ['action' => 'xlsx',], 'type'=>'GET']); ?>
                        <input type="text" name="condominio" value="<?= $id_condominio ?>" hidden>
                        <input type="submit" value="Descargar Excel" id="buscar" class="btn btn-primary">
                    <?= $this->Form->end() ?>
                </ul>
            </div>
        </div>
    </div>
    <br><br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-wpforms"></i> Cuotas</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('condominio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_tipo', ['Tipo']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('cuota') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cuotas as $cuota): ?>
            <tr>
                <td><?= h($cuota['condominio']) ?></td>
                <td><?= h($cuota['tipo']) ?></td>
                <td><?= h($cuota['cuota']) ?></td>
                <td><?= $this->Time->format($cuota['fecha'],'d MMMM Y') ?></td>
                <td class="actions">
                     <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                      <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $cuota['id_cuotas']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Editar">
                        <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $cuota['id_cuotas']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Borrar">
                        <button type="button" class="btn btn-xs" id="delete" name="delete"
                                data-id="<?= $cuota['id_cuotas']?>" data-cuota="<?= $cuota['cuota'] ?>"><i
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
<div class="modal fade" id="modalBorrar" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desea usted eliminar la Cuota?</h5>
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
        $('#table button[type="button"]').each(function (index, element) { //index: N. objetos dentro del table, element: objeto que se esta buscando
            console.log("Row ..........");
            $(element).on('click', function () {
                var button = $(this);
                $('#modalBorrar').on('show.bs.modal', function (event) {
                    var idMino = button.attr('data-id');
                    console.log('El pago a borrar: ' + idMino);
                    $(event.currentTarget).find('button[id="borrar"]').attr('data-id', idMino);
                    $('#modal').data('data-id', idMino);
                    var modal = $(this);
                    modal.find('.modal-body').html(button.data('cuota'));
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
            url: '/cuotas/borrar/' + id,
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
