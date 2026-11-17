<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Supervision $supervision
 */
?>
<div class="col-md-1">
    <i class="fa fa-caret-left" style="color: #0056b3;"></i> <?= $this->Html->link(__('Regresar'), ['action' => 'index', 'id'=>$id_condominio]) ?>
</div>
<br>
<br>
<div class="col-md-12">
    <?= $this->Form->create($supervision) ?>
    <fieldset>
        <h1>Reporte de supervisión</h1>
        <p>
            Indica el área de la cual vas a tomar reporte, tomá una fotografía como evidencia y completa el cuadro de observaciones para concluir con el área reportada, a continuación presiona el botón de guardar para poder capturar la siguiente área, al terminar presiona el botón de "Ver reporte"        </p>
        <?php
            echo $this->Form->control('id_condominio', ['value'=> $id_condominio, 'type' => 'hidden']);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Continuar')) ?>
    <?= $this->Form->end() ?>
</div>
