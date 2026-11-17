<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ticket $ticket
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Ticket'), ['action' => 'edit', $ticket->id_ticket]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Ticket'), ['action' => 'delete', $ticket->id_ticket], ['confirm' => __('Are you sure you want to delete # {0}?', $ticket->id_ticket)]) ?> </li>
        <li><?= $this->Html->link(__('List Ticket'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Ticket'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="ticket view large-9 medium-8 columns content">
    <h3><?= h($ticket->id_ticket) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Nombre') ?></th>
            <td><?= h($ticket->nombre) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Prioridad') ?></th>
            <td><?= h($ticket->prioridad) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Asunto') ?></th>
            <td><?= h($ticket->asunto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Dir') ?></th>
            <td><?= h($ticket->dir) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Adjunto') ?></th>
            <td><?= h($ticket->adjunto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Ticket') ?></th>
            <td><?= $this->Number->format($ticket->id_ticket) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($ticket->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($ticket->id_condomino) ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Mensaje') ?></h4>
        <?= $this->Text->autoParagraph(h($ticket->mensaje)); ?>
    </div>
</div>
