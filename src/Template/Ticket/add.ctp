<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ticket $ticket
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Ticket'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="ticket form large-9 medium-8 columns content">
    <?= $this->Form->create($ticket) ?>
    <fieldset>
        <legend><?= __('Add Ticket') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_condomino');
            echo $this->Form->control('nombre');
            echo $this->Form->control('prioridad');
            echo $this->Form->control('asunto');
            echo $this->Form->control('mensaje');
            echo $this->Form->control('dir');
            echo $this->Form->control('adjunto');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
