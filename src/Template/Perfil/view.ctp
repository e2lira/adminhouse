<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Perfil $perfil
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Perfil'), ['action' => 'edit', $perfil->id_perfil]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Perfil'), ['action' => 'delete', $perfil->id_perfil], ['confirm' => __('Are you sure you want to delete # {0}?', $perfil->id_perfil)]) ?> </li>
        <li><?= $this->Html->link(__('List Perfil'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Perfil'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="perfil view large-9 medium-8 columns content">
    <h3><?= h($perfil->id_perfil) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Perfil') ?></th>
            <td><?= h($perfil->perfil) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Perfil') ?></th>
            <td><?= $this->Number->format($perfil->id_perfil) ?></td>
        </tr>
    </table>
</div>
