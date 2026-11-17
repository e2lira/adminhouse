<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoPago $tipoPago
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
                    <li><?= $this->Html->link(__('Lista de Tipos de Pago'), ['action' => 'index', 'id'=>$id_condominio]) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <br><br>
<div class="tipoPago form col-md-6">
    <?= $this->Form->create($tipoPago) ?>
    <fieldset>
        <legend><?= __('Agregar Tipo de Pago') ?></legend>
        <?php
            echo $this->Form->control('nombre');
            echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Guardar')) ?>
    <?= $this->Form->end() ?>
</div>
