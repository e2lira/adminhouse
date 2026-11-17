<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ticket $ticket
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Lista de Ticket'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="ticket form large-9 medium-8 columns content">
    <?= $this->Form->create($ticket) ?>
    <fieldset>
        <legend><?= __('Editar estatuis Ticket') ?></legend>
        <?php

            echo $this->Form->control('estatus',['options'=>['Activo'=>'Activo', 'En Proceso'=>'En Proceso', 'Cerrado'=>'Cerrado']]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
