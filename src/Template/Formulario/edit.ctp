<?php
	echo $this->Html->script('SweetAlertHelper.sweetalert2.min');

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Formulario $formulario
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $formulario->id_formulario],
                ['confirm' => __('Are you sure you want to delete # {0}?', $formulario->id_formulario)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Formulario'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="formulario form large-9 medium-8 columns content">
    <?= $this->Form->create($formulario) ?>
    <fieldset>
        <legend><?= __('Edit Formulario') ?></legend>
        <?php
            echo $this->Form->control('fecha_creacion', ['empty' => true]);
            echo $this->Form->control('nombre_elaboro');
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('estancia');
            echo $this->Form->control('imagen_estancia');
            echo $this->Form->control('observaciones_estancia');
            echo $this->Form->control('escalera');
            echo $this->Form->control('observaciones_escalera');
            echo $this->Form->control('imagen_escalera');
            echo $this->Form->control('pasillo');
            echo $this->Form->control('imagen_pasillo');
            echo $this->Form->control('observaciones_pasillo');
            echo $this->Form->control('elevadores');
            echo $this->Form->control('observaciones_elevadores');
            echo $this->Form->control('planta_luz');
            echo $this->Form->control('imagen_planta_luz');
            echo $this->Form->control('observaciones_planta_luz');
            echo $this->Form->control('cisterna');
            echo $this->Form->control('imagen_cisterna');
            echo $this->Form->control('observaciones_cisterna');
            echo $this->Form->control('bomba');
            echo $this->Form->control('imagen_bomba');
            echo $this->Form->control('observaciones_bomba');
            echo $this->Form->control('estacionamiento');
            echo $this->Form->control('imagen_estacionamiento');
            echo $this->Form->control('observaciones_estacionamiento');
            echo $this->Form->control('caldera');
            echo $this->Form->control('imagen_caldera');
            echo $this->Form->control('observaciones_caldera');
            echo $this->Form->control('gimnasio');
            echo $this->Form->control('imagen_gimnasio');
            echo $this->Form->control('observaciones_gimnasio');
            echo $this->Form->control('salon');
            echo $this->Form->control('imagen_salon');
            echo $this->Form->control('observaciones_salon');
            echo $this->Form->control('roof');
            echo $this->Form->control('imagen_roof');
            echo $this->Form->control('observaciones_roof');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
