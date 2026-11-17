<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UsuarioCondomino $usuarioCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Usuario Condomino'), ['action' => 'edit', $usuarioCondomino->id_pibote]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Usuario Condomino'), ['action' => 'delete', $usuarioCondomino->id_pibote], ['confirm' => __('Are you sure you want to delete # {0}?', $usuarioCondomino->id_pibote)]) ?> </li>
        <li><?= $this->Html->link(__('List Usuario Condomino'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Usuario Condomino'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="usuarioCondomino view large-9 medium-8 columns content">
    <h3><?= h($usuarioCondomino->id_pibote) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Pibote') ?></th>
            <td><?= $this->Number->format($usuarioCondomino->id_pibote) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($usuarioCondomino->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($usuarioCondomino->id_condomino) ?></td>
        </tr>
    </table>
</div>
