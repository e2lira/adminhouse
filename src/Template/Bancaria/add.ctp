<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bancarium $bancarium
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3" id="actions-sidebar">
        <ul class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Cuentas Bancarias'), ['action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                </ul>
            </div>
        </ul>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($bancarium) ?>
        <fieldset>
            <legend><?= __('Agregar Cuenta Bancaria') ?></legend>
            <?php
                echo $this->Form->input('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
                echo $this->Form->control('nombre_banco');
                echo $this->Form->control('no_cuenta');
                echo $this->Form->control('estatus', [
                'options' => ['activo' => 'Activo', 'inactivo' => 'Inactivo']]);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Guardar')) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
