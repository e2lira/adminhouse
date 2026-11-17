<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Respuesta $respuesta
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Respuesta'), ['action' => 'edit', $respuesta->id_respuestas]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Respuesta'), ['action' => 'delete', $respuesta->id_respuestas], ['confirm' => __('Are you sure you want to delete # {0}?', $respuesta->id_respuestas)]) ?> </li>
        <li><?= $this->Html->link(__('List Respuestas'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Respuesta'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="respuestas view large-9 medium-8 columns content">
    <h3><?= h($respuesta->id_respuestas) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Mensaje') ?></th>
            <td><?= h($respuesta->mensaje) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Dir') ?></th>
            <td><?= h($respuesta->dir) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Adjunto') ?></th>
            <td><?= h($respuesta->adjunto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Respuestas') ?></th>
            <td><?= $this->Number->format($respuesta->id_respuestas) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Mensaje') ?></th>
            <td><?= $this->Number->format($respuesta->id_mensaje) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($respuesta->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($respuesta->id_condomino) ?></td>
        </tr>
    </table>
</div>
