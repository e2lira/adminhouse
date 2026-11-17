<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AmenidadCondominio $amenidadCondominio
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Amenidad Condominio'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="amenidadCondominio form large-9 medium-8 columns content">
    <?= $this->Form->create($amenidadCondominio) ?>
    <fieldset>
        <legend><?= __('Add Amenidad Condominio') ?></legend>
        <?php
            echo $this->Form->control('id_amenidad');
            echo $this->Form->control('id_condominio');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
