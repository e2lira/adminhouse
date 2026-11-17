<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCondominio $saldoCondominio
 */
?>
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
    Saldo en contra
</button>
<div class="row">
    <!-- Button trigger modal -->

    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Saldo Inicial'), ['action' => 'index', 'id'=>$condomino['id_condomino']]) ?></li>
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
                </ul>
                <br>

            </div>
        </div>
        <br><br>

    </div>

    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($saldoCondomino) ?>
        <fieldset>
            <legend><?= __('Agregar Saldo Inicial a Favor') ?></legend>
            <?php
            echo $this->Form->control('id_condomino',['type' => 'hidden', 'value'=>$condomino->id_condomino]);
            echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$id_condominio]);
            echo $this->Form->control('saldo');
            echo $this->Form->control('fecha');
            ?>
        </fieldset>
        <?= $this->Form->button(__('Guardar')) ?>
        <?= $this->Form->end() ?>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Saldo inicial </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
                        <?= $this->Form->create($saldoCondomino) ?>
                        <fieldset>
                            <legend><?= __('Agregar Saldo Inicial En contra') ?></legend>
                            <?php
                            echo $this->Form->control('id_condomino',['type' => 'hidden', 'value'=>$condomino->id_condomino]);
                            echo $this->Form->control('contra', ['label'=>'Saldo']);
                            echo $this->Form->control('fecha');
                            ?>
                        </fieldset>
                        <?= $this->Form->button(__('Guardar')) ?>
                        <?= $this->Form->end() ?>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
