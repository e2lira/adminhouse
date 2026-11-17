<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Comunicado $comunicado
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Comunicado'), ['action' => 'edit', $comunicado->id_comunicado]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Comunicado'), ['action' => 'delete', $comunicado->id_comunicado], ['confirm' => __('Are you sure you want to delete # {0}?', $comunicado->id_comunicado)]) ?> </li>
        <li><?= $this->Html->link(__('List Comunicado'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Comunicado'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="comunicado view large-9 medium-8 columns content">
    <h3><?= h($comunicado->id_comunicado) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Titulo') ?></th>
            <td><?= h($comunicado->titulo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Dir') ?></th>
            <td><?= h($comunicado->dir) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Adjunto') ?></th>
            <td><?= h($comunicado->adjunto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Comunicado') ?></th>
            <td><?= $this->Number->format($comunicado->id_comunicado) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($comunicado->id_condominio) ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Comunicado') ?></h4>
        <?= $this->Text->autoParagraph(h($comunicado->comunicado)); ?>
    </div>
</div>
