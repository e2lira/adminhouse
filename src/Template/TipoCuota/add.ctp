<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoCuotum $tipoCuotum
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Acciones') ?></li>
        <li><?= $this->Html->link(__('Lista de Tipos de Cuota'), ['action' => 'index', 'id'=>$id_condominio]) ?></li>
    </ul>
</nav>
<div class="tipoCuota form large-9 medium-8 columns content">
    <?= $this->Form->create($tipoCuotum) ?>
    <fieldset>
        <legend><?= __('Agregar Tipo de Cuota') ?></legend>
        <?php
            echo $this->Form->control('tipo_cuota');
            echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);

        ?>
    </fieldset>
    <?= $this->Form->button(__('Guardar')) ?>
    <?= $this->Form->end() ?>
</div>
