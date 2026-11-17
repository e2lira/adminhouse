<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Visita $visita
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $visita->id_visita],
                ['confirm' => __('Are you sure you want to delete # {0}?', $visita->id_visita)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Visitas'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="visitas form large-9 medium-8 columns content">
    <?= $this->Form->create($visita) ?>
    <fieldset>
        <legend><?= __('Edit Visita') ?></legend>
        <?php
            echo $this->Form->control('nombre');
            echo $this->Form->control('departamento');
            echo $this->Form->control('fecha');
            echo $this->Form->control('hora');
            echo $this->Form->control('tipo_visita');
            echo $this->Form->control('auto');
            echo $this->Form->control('color');
            echo $this->Form->control('marca');
            echo $this->Form->control('placas');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
