<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Gasto[]|\Cake\Collection\CollectionInterface $gasto
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
                    <li><?= $this->Html->link(__('Lista de Condominios'), ['controller' => 'Condominio', 'action' =>
                        'index']) ?>
                    </li>
                    <li><?= $this->Html->link(__('Nuevo Egresos'), ['controller' => 'Gasto', 'action' => 'add', 'id'=>$condominio['id_condominio']]) ?></li>

                </ul>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-dollar"></i> Gasto</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
                <thead>
                <tr>
                    <th scope="col"><?= $this->Paginator->sort('id_tipo') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('gasto') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('concepto') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('referencia') ?></th>
                    <th scope="col" class="actions"><?= __('Actions') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($gasto as $gasto): ?>
                <tr>
                    <td><?= h($gasto['tipo']) ?></td>
                    <td><?= h($gasto['gasto']) ?></td>
                    <td><?= h($gasto['concepto']) ?></td>
                    <td><?= $this->Time->format($gasto['fecha'],'d MMMM Y') ?></td>
                    <td><?= h($gasto['referencia']) ?></td>
                    <td class="actions">
                     <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                    <?= $this->Html->link("<i class='fa fa-check-square'></i>", ['action' => 'confirmacion', $gasto['id_gasto']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                    <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $gasto['id_gasto']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Editar">
                    <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $gasto['id_gasto']], ['escape' => false]) ?>
                    </span>
                    <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Borrar">
                    <button type="button" class="btn btn-xs" id="delete" name="delete"
                            data-id="<?= $gasto['id_gasto'] ?>" data-concepto="<?= $gasto['concepto'] ?>"><i
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
                <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}}
                    record(s) out of {{count}} total')]) ?></p>
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
                <h5 class="modal-title">Desea usted eliminar el Gasto?</h5>
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
                    modal.find('.modal-body').html(button.data('concepto'));
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
            url: '/pago/borrar/' + id,
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
