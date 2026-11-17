<div class="jumbotron">
    <h1 class="display-4">Usuario + Condomino</h1>
    <p class="lead">En el siguiente recuadro podras asignar un condomino a un usuario</p>
    <hr class="my-4">
    <p>Selecciona una de las siguientes opciones.</p>
    <p class="lead">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
            Asignar
        </button>    </p>
</div>
<br>
<div class="table-responsive">
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"><?= $this->Paginator->sort('id_user') ?></th>
            <th scope="col"><?= $this->Paginator->sort('id_condomino') ?></th>
            <th scope="col" class="actions"><?= __('Actions') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($asigna as $asigna): ?>
            <tr>
                <td><?= $this->Number->format($asigna->id_user) ?></td>
                <td><?= $this->Number->format($asigna->id_condomino) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $asigna->id_asigna]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $asigna->id_asigna], ['confirm' => __('Are you sure you want to delete # {0}?', $asigna->id_asigna)]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('primero')) ?>
            <?= $this->Paginator->prev('< ' . __('anterior')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('siguiente') . ' >') ?>
            <?= $this->Paginator->last(__('ultimo') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(['format' => __('Pagina {{page}} de {{pages}}, mostrando {{current}} registro(s) de {{count}} total')]) ?></p>
    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Asigna</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="asigna form large-9 medium-8 columns content">
                    <?php
                    echo $this->Form->input('Condominio',['type' => 'select',
                        'options' => $options,
                        'empty' => 'selecciona']);
                    ?>
                    <?= $this->Form->create($asigna) ?>
                    <fieldset>
                        <?php

                        echo $this->Form->input('id_condomino',['type' => 'select',
                            'options' => $condomino,
                            'empty' => 'selecciona']);

                        echo $this->Form->input('id_users',['type' => 'select',
                            'options' => $users,
                            'empty' => 'selecciona']);
                        ?>
                    </fieldset>
                    <?= $this->Form->button(__('Guardar')) ?>
                    <?= $this->Form->end() ?>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
             </div>
        </div>
    </div>
</div>
