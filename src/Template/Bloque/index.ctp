<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bloque[]|\Cake\Collection\CollectionInterface $bloque
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
                    <li><?= $this->Html->link(__('Asignar cuota a condomino'), ['action' => 'add',
                            'id'=>$id_condominio]) ?>
                    </li>
                    <br>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                        Cargar Cuotas
                    </button>
                </ul>
            </div>
        </div>
    </div>
    <br><br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-wpforms"></i> Cuotas Indiviso en Bloque</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_condomino',['label'=>'Condomino']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('cuota') ?></th>
                <th scope="col"><?= $this->Paginator->sort('concepto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bloque as $bloque): ?>
            <tr>
                <td><?= $this->Number->format($bloque->id_condomino) ?></td>
                <td><?= $this->Number->format($bloque->cuota) ?></td>
                <td><?= h($bloque->concepto) ?></td>
                <td><?= $this->Time->format($bloque['fecha'],'d MMMM Y') ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Ver'), ['action' => 'view', $bloque->id]) ?>
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $bloque->id]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $bloque->id], ['confirm' => __('Are you sure you want to delete # {0}?', $bloque->id)]) ?>
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
        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Cargar cuotas indiviso</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                    <p>¿Estas seguro de querer cargar cuotas a los condominios por bloque?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="guardar" data-X-CSRF-Token="<?= $_csrfToken ?>" data-id-condominio="<?= $id_condominio?>">Cargar</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <?php echo $this->Html->scriptBlock(sprintf(
                        'var csrfToken = %s;',
                        json_encode($this->request->getAttribute('csrfToken'))
                    )); ?>
                    </div>
                </div>
            </div>
        </div>
        <script>
            var csrfToken = <?= json_encode($this->request->getParam('_csrfToken')) ?>;
            console.log(csrfToken);

            $( document ).ready(function() {
                console.log('Saludos desde jQuery');

                //Guardar en adeudos

                $("#guardar").click(function(){
                    var csrf = $('#guardar').attr('data-X-CSRF-Token');
                    console.log("Boton guardar"+csrf);
                    $.ajax({
                        async: true,
                        cache: false,
                        url: '/Bloque/saveAdeudo/'+ $('#guardar').attr('data-id-condominio')
                        ,
                        type: 'POST',
                        dataType: 'json',
                        beforeSend: function(xhr){
                            xhr.setRequestHeader(
                                'X-CSRF-Token',csrf
                            );
                        },
                        success:function(response) {
                            console.log(response);
                            alert("Se guardo con exito");
                        },
                        error: function(response) {
                            //console.error(response.message, response.title);
                        }
                    });
                });

            });
        </script>

