<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Comunicado $comunicado
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">


    </div>
    <br><br>
    <div class="col-md-12 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($comunicado, ['type' => 'file']) ?>
        <fieldset>
            <legend><?= __('Editar Comunicado') ?></legend>
            <?php
            echo $this->Form->control('titulo');
            echo $this->Form->control('comunicado');
            echo $this->Form->control('adjunto', ['type' => 'file']);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Guardar')) ?>
        <?= $this->Form->end() ?>
    </div>
