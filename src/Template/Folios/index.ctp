<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Folio[]|\Cake\Collection\CollectionInterface $folios
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
                <li><?= $this->Html->link(__('List Condominio'), ['controller' => 'Condominio', 'action' =>
                    'index']) ?>
                </li>
            </ul>
        </div>
    </div>
</div>
<br><br>

    <div class="col-xs-12 col-sm-12 col-md-9 ">
    <h3><?= __('Folios') ?></h3>
    <div class="table-responsive">
        <table class="table table-striped table-hover  table-sm" id="table">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_condominio',['Condominio']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('serie') ?></th>
                <th scope="col"><?= $this->Paginator->sort('incremento') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha_alta') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($folios as $folio): ?>
            <tr>
                <td><?= h($folio['condominio']) ?></td>
                <td><?= h($folio['serie']) ?></td>
                <td><?= h($folio['incremento']) ?></td>
                <td><?= h($folio['fecha_alta']) ?></td>
                <td class="actions">
                       <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                      <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $folio['id']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Editar">
                        <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $folio['id']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Borrar">
                        <button type="button" class="btn btn-xs" id="delete" name="delete"
                                data-id="<?= $folio['id']?>" data-cuota="<?= $folio['serie'] ?>"><i
                                class='fa fa-trash'></i></button>
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
            url: '/folios/borrar/' + id,
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