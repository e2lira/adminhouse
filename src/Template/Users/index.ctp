
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 ">
        <div class="card">
            <div class="container">
                <h2>Usuario</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Nuevo Usuario'), ['action' => 'add']) ?></li>
                    <li><?= $this->Html->link(__('Asignar Perfil'), ['controller' => 'Users', 'action' => 'asigna']) ?></li>
                </ul>
            </div>
        </div>
        <br>
        <div class="card">
            <div class="container">
                <h2>Administración</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['controller' => 'Condominio', 'action' => 'index-completo']) ?></li>
                    <li><?= $this->Html->link(__('Nuevo Condominio'), ['controller' => 'Condominio', 'action' => 'add']) ?></li>
                    <li><?= $this->Html->link(__('Tipo Egreso'), ['controller' => 'Tipo_gasto', 'action' => 'index']) ?></li>
                    <li><?= $this->Html->link(__('Tipo Ingreso'), ['controller' => 'Tipo_pago', 'action' => 'index']) ?></li>
                    <li><?= $this->Html->link(__('Tipo Cuota'), ['controller' => 'Tipo_cuota', 'action' => 'index']) ?></li>

                </ul>
            </div>
        </div>
    </div>
    <br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-users"></i> Usuarios</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col"><?= $this->Paginator->sort('username') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('perfil') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('email') ?></th>
                    <th scope="col" class="actions"><?= __('Actions') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= h($user['username']) ?></td>
                    <td><?= h($user['perfil']) ?></td>
                    <td><?= h($user['email']) ?></td>
                    <td class="actions">
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Ver">
                        <?= $this->Html->link("<i class='fa fa-eye'></i>", ['action' => 'view', $user['id_user']], ['escape' => false]) ?>
                        </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Editar">
                        <?= $this->Html->link("<i class='fa fa-edit'></i>", ['action' => 'edit', $user['id_user']], ['escape' => false]) ?>
                        </span>
                        <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Borrar">
                        <button type="button" class="btn btn-xs" id="delete" name="delete" data-id-user="<?= $user['id_user'] ?>" data-username="<?= $user['username'] ?>"><i class='fa fa-trash'></i></button>
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

<!-- Modal -->
<div class="modal fade" id="modalBorrado" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Desea usted eliminar al usuario?</h4>
            </div>
            <div class="modal-body">
                <p>Se elimino con exito</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
                    var idUser = button.attr('data-id-user');
                    console.log('El usuario a borrar: ' + idUser);
                    $(event.currentTarget).find('button[id="borrar"]').attr('data-id', idUser);
                    $('#modal').data('data-id', idUser);
                    var modal  = $(this);
                    modal.find('.modal-body').html(button.data('username'));
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
            url: '/users/borrar/' + id,
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

    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
