<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Gasto $gasto
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($gasto) ?>
        <fieldset>
            <legend><?= __('Confirmar Gasto') ?></legend>
            <?php
            echo $this->Form->input('confirmacion',['type' => 'select',
                'options' => ['SI'=>'SI', 'NO'=>'NO', 'COBRADO'=>'COBRADO'],
                'empty' => 'Selecciona']);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Guardar')) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
