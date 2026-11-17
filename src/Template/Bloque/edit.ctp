<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bloque $bloque
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $bloque->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $bloque->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Bloque'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="bloque form large-9 medium-8 columns content">
    <?= $this->Form->create($bloque) ?>
    <fieldset>
        <legend><?= __('Edit Bloque') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_condomino');
            echo $this->Form->control('id_tipo');
            echo $this->Form->control('cuota');
            echo $this->Form->control('cuota_letra');
            echo $this->Form->control('concepto');
            echo $this->Form->control('fecha_cargo', ['empty' => true]);
            echo $this->Form->control('fecha', ['empty' => true]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
