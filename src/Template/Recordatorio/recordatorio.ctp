<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condominio $condominio
 */
echo $this->Html->script('jquery.MultiFile.js');

?>
<div class="row">
    <div class="col-md-4 col-sm-12 col-xs-12 col-lg-4" id="actions-sidebar">
        <ul class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Recordatorios'), ['controller' => 'Recordatorio', 'action' => 'index']) ?></li>
                    <br>

                    <br>
                </ul>
            </div>
        </ul>

        <ul class="card">
            <div class="container">
                <h2>Agenda</h2>
            </div>
            <div>
                <table class="table table-hover  table-sm" id="tableCond">
                    <thead>
                    <tr>
                        <th scope="col"><?= $this->Paginator->sort('numero_departamento', ['Departamento']) ?></th>
                        <th scope="col"><?= $this->Paginator->sort('correo_contacto', ['Correo']) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($condomino as $condomino): ?>
                        <tr>
                            <td><?= h($condomino['numero_departamento']) ?></td>
                            <td><?= h($condomino['correo_contacto']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </ul>

    </div>



    <div class="col-md-7 col-sm-12 col-xs-12 col-lg-7" style=" border: 1px solid #545b62; border-radius: 7px;">
        <fieldset>

            <div class="row">
                <div class="col-md-10">
                    <h5>Mensaje Nuevo</h5>
                </div>


            </div>

            <div class="recordatorio form large-9 medium-8 columns content">
                <?= $this->Form->create($recordatorio,['type' => 'file']) ?>
                <fieldset>
                    <?php
                    echo $this->Form->control('destinatarios._ids', ['type'=> 'select',
                        'options' => $correos,
                        'multiple'=>'multiple']);
                    echo $this->Form->control('asunto');
                    echo $this->Form->control('mensaje');
                    echo $this->Form->control('adjunto', ['type' => 'file']);
                    ?>
                </fieldset>
                <?= $this->Form->button(__('Submit')) ?>
                <?= $this->Form->end() ?>
            </div>
    </div>

    <div class="col-md-1 col-sm-1 col-xs-1 col-lg-1" >

    </div>

</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Estado de cuenta</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Estas seguro de enviar por correo el estado de cuenta a los condiminos que ya seleccinaste?
                <br>
                <br>
                <input type="button" id="yes" value="Enviar" data-dismiss="modal">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
           </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        console.log("Aqui ando");
        $('#destinatarios-ids').select2({
            cache: true,
            serverSide: true,
            placeholder:'Para:',
            dropdownAutoWidth: true,
            multiple: true,
            theme: "bootstrap4",
            ajax: {
                url:  '/recordatorio/getCorreos/',
                dataType: 'json',
                data: function(params) {
                    var query={
                        search: params.term,
                        page: params.page || 1,
                        size: '5'
                    }
                    return query;
                }
            }
        });

    });

    $(function(){
        $('#all').on('click', function(){
            $('#destinatarios-ids').attr('disabled', $(this).is(':checked'));
        });
    });
</script>
