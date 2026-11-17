<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bancarium $bancarium
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Bancarium'), ['action' => 'edit', $bancarium->id_bancaria]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Bancarium'), ['action' => 'delete', $bancarium->id_bancaria], ['confirm' => __('Are you sure you want to delete # {0}?', $bancarium->id_bancaria)]) ?> </li>
        <li><?= $this->Html->link(__('List Bancaria'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Bancarium'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="bancaria view large-9 medium-8 columns content">
    <h3><?= h($bancarium->id_bancaria) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Nombre Banco') ?></th>
            <td><?= h($bancarium->nombre_banco) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Estatus') ?></th>
            <td><?= h($bancarium->estatus) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Bancaria') ?></th>
            <td><?= $this->Number->format($bancarium->id_bancaria) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($bancarium->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('No Cuenta') ?></th>
            <td><?= $this->Number->format($bancarium->no_cuenta) ?></td>
        </tr>
    </table>
</div>
