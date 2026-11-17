<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Recordatorio $recordatorio
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Recordatorio'), ['action' => 'edit', $recordatorio->id_recordatorio]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Recordatorio'), ['action' => 'delete', $recordatorio->id_recordatorio], ['confirm' => __('Are you sure you want to delete # {0}?', $recordatorio->id_recordatorio)]) ?> </li>
        <li><?= $this->Html->link(__('List Recordatorio'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Recordatorio'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="recordatorio view large-9 medium-8 columns content">
    <h3><?= h($recordatorio->id_recordatorio) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Destinatarios') ?></th>
            <td><?= h($recordatorio->destinatarios) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Asunto') ?></th>
            <td><?= h($recordatorio->asunto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Adjunto') ?></th>
            <td><?= h($recordatorio->adjunto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Dir') ?></th>
            <td><?= h($recordatorio->dir) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Recordatorio') ?></th>
            <td><?= $this->Number->format($recordatorio->id_recordatorio) ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Mensaje') ?></h4>
        <?= $this->Text->autoParagraph(h($recordatorio->mensaje)); ?>
    </div>
</div>
