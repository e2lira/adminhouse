<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Folio $folio
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
                    <li><?= $this->Html->link(__('List Condominio'), ['controller' => 'Condominio', 'action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                    <li><?= $this->Html->link(__('List Folios'), ['controller' => 'Folios', 'action' => 'index']) ?></li>
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
        <br><br>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($folio) ?>
    <fieldset>
        <legend><?= __('Add Folio') ?></legend>
        <?php
        echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);

        //echo $this->Form->input('id_condominio',['type' => 'select',
        //    'options' => $optionsCondominio,
        //    'empty' => 'selecciona']);

        echo $this->Form->control('serie');
            echo $this->Form->control('incremento');
            echo $this->Form->control('fecha_alta');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
  </div>
</div>