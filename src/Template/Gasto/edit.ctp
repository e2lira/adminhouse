<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Gasto $gasto
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
                    <li><?= $this->Form->postLink(
                        __('Eliminar'),
                        ['action' => 'delete', $gasto->id_gasto],
                        ['confirm' => __('Are you sure you want to delete # {0}?', $gasto->id_gasto)]
                        )
                        ?></li>
                    <li><?= $this->Html->link(__('Lista de Gastos'), ['controller' => 'Gasto', 'action' => 'index', $gasto['id_condominio']]) ?> </li>
                    <li><?= $this->Html->link(__('Lists de Condominios'), ['controller' => 'Condominio', 'action' => 'index']) ?> </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($gasto,['type' => 'file']) ?>
        <fieldset>
            <legend><?= __('Editar Egreso') ?></legend>
            <?php

            echo $this->Form->input('id_tipo',['type' => 'select',
            'options' => $options,
            'empty' => 'selecciona']);

            echo $this->Form->input('id_proveedores',['type' => 'select',
            'options' => $optionProveedor,
            'empty' => 'selecciona']);

            echo $this->Form->control('gasto');
            echo $this->Form->control('monto_letra');
            echo $this->Form->control('concepto');
            echo $this->Form->control('referencia');
            echo $this->Form->input('confirmacion',['type' => 'select',
                'options' => ['SI'=>'SI', 'NO'=>'NO'],
                'empty' => 'Selecciona']);
            ?>
        </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
 </div>
</div>
