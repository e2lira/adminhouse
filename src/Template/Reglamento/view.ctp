<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Reglamento $reglamento
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Reglamento'), ['action' => 'edit', $reglamento->id_reglamento]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Reglamento'), ['action' => 'delete', $reglamento->id_reglamento], ['confirm' => __('Are you sure you want to delete # {0}?', $reglamento->id_reglamento)]) ?> </li>
        <li><?= $this->Html->link(__('List Reglamento'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Reglamento'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="reglamento view large-9 medium-8 columns content">
    <h3><?= h($reglamento->id_reglamento) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Nombre') ?></th>
            <td><?= h($reglamento->nombre) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Reglamento') ?></th>
            <td><?= $this->Number->format($reglamento->id_reglamento) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($reglamento->id_condominio) ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Reglamento') ?></h4>
        <?= $this->Text->autoParagraph(h($reglamento->reglamento)); ?>
    </div>
</div>
