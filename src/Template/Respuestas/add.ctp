<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Respuesta $respuesta
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Respuestas'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="respuestas form large-9 medium-8 columns content">
    <?= $this->Form->create($respuesta) ?>
    <fieldset>
        <legend><?= __('Add Respuesta') ?></legend>
        <?php
            echo $this->Form->control('id_mensaje');
            echo $this->Form->control('mensaje');
            echo $this->Form->control('dir');
            echo $this->Form->control('adjunto');
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_condomino');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
