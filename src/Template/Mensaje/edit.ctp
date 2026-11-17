<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mensaje $mensaje
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $mensaje->id_mensaje],
                ['confirm' => __('Are you sure you want to delete # {0}?', $mensaje->id_mensaje)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Mensaje'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="mensaje form large-9 medium-8 columns content">
    <?= $this->Form->create($mensaje) ?>
    <fieldset>
        <legend><?= __('Edit Mensaje') ?></legend>
        <?php
            echo $this->Form->control('titulo');
            echo $this->Form->control('mensaje');
            echo $this->Form->control('dir');
            echo $this->Form->control('adjunto');
            echo $this->Form->control('fecha', ['empty' => true]);
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_condomino');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
