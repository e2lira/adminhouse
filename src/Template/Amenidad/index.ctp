<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Amenidad[]|\Cake\Collection\CollectionInterface $amenidad
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
                    <li class="heading"><?= __('Acciones') ?></li>
                    <li><?= $this->Html->link(__('Nueva Amenidad'), ['action' => 'add',
                            'id'=>$id_condominio]) ?></li>
                    <li><?= $this->Html->link(__('Lista de Condominios'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-book"></i> Amenidades</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('amenidad') ?></th>
                <th scope="col"><?= $this->Paginator->sort('color') ?></th>
                <th scope="col"><?= $this->Paginator->sort('no_usuarios') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($amenidad as $amenidad): ?>
            <tr>
                <td><?= h($amenidad->amenidad) ?></td>
                <td style="color: <?= h($amenidad->color) ?>"><?= h($amenidad->color) ?></td>
                <td><?= h($amenidad->no_usuarios) ?></td>

                <td class="actions">
                    <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $amenidad['id_amenidad']], ['escape' => false]) ?>
                    <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $amenidad['id_amenidad']], ['escape' => false]) ?>
                    <button type="button" class="btn btn-xs" id="delete" name="delete" data-id="<?= $amenidad['id_amenidad'] ?>" data-name="<?= $amenidad['amenidad'] ?>"><i class='fa fa-trash'></i></button>
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
<div class="modal fade" id="modalUsers" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desea usted eliminar al usuario?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>The <strong>show.bs.modal</strong> event occurs when the modal is about to be shown.</p>
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
        $('#table button[type="button"]').each(function (index, element) { //index: N. objetos dentro del table, element: objeto que se esta buscando
            console.log("Row ..........");
            $(element).on('click', function () {
                var button = $(this);
                $('#modalUsers').on('show.bs.modal', function (event) {
                    var idUser = button.attr('data-id');
                    console.log('El usuario a borrar: ' + idUser);
                    $(event.currentTarget).find('button[id="borrar"]').attr('data-id', idUser);
                    $('#modal').data('data-id', idUser);
                    var modal  = $(this);
                    modal.find('.modal-body').html(button.data('name'));
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
            url: '/amenidad/borrar/' + id,
            dataType: 'json',
            success: function(msg) {
                result = msg[0];
                console.log(result.message);
                if (result.result === 'ok') {
                    delete(id);
                    $('#modalBorrado').modal('show');
                    $('#modalUsers').modal('hide');
                    window.location.reload();


                }
            }
        });
    });
</script>
