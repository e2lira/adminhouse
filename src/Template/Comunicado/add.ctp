<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Comunicado $comunicado
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Comunicados'), ['controller' => 'Comunicado', 'action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="container">
                <h3>Condominio: <?= $condominio->nombre_condominio ?></h3>
                <ul style="color: #0f0f0f">
                    <li><span>Nombre:</span> <?= $condominio->nombre_condominio ?></li>
                    <li><span>Direccion:</span> <?= $condominio->direccion_condominio ?></li>
                    <li><span>Telefono:</span> <?= $condominio->telefono_condominio ?></li>
                    <li><span>No. Cuenta:</span> <?= $condominio->no_cuenta_condominio ?></li>
                    <li><span>Banco:</span> <?= $condominio->banco_cuenta ?></li>
                </ul>
            </div>
        </div>
        </div>
        <br><br>
        <div class="col-md-12 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($comunicado, ['type' => 'file']) ?>
    <fieldset>
        <legend><?= __('Agregar Comunicado') ?></legend>
        <?php
            echo $this->Form->control('titulo');
            echo $this->Form->control('comunicado');
            echo $this->Form->control('adjunto', ['type' => 'file']);
            echo $this->Form->input('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Guardar')) ?>
    <?= $this->Form->end() ?>
</div>
