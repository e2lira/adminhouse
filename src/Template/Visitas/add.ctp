<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Visita $visita
 */
?>
<div class="row">
<div class="col-md-3" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Lista de Visitas'), ['action' => 'index']) ?></li>
    </ul>
</div>
<div class="col-md-9">
    <?= $this->Form->create($visita) ?>
    <fieldset>
        <legend><?= __('Agregar Visita') ?></legend>
        <?php
            echo $this->Form->control('nombre');
            echo $this->Form->control('departamento');
            echo $this->Form->control('fecha');
            echo $this->Form->control('hora');
            echo $this->Form->input('tipo_visita',['type' => 'select',
                'options' => $optionsTipo,
            'empty' => 'selecciona']);
            echo $this->Form->control('auto');
            echo $this->Form->control('color');
            echo $this->Form->control('marca');
            echo $this->Form->control('placas');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
</div>
