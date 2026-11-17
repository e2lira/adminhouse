<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Pendiente $pendiente
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $pendiente->id_pendientes],
                ['confirm' => __('Are you sure you want to delete # {0}?', $pendiente->id_pendientes)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Pendientes'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="pendientes form large-9 medium-8 columns content">
    <?= $this->Form->create($pendiente) ?>
    <fieldset>
        <legend><?= __('Edit Pendiente') ?></legend>
        <?php
            echo $this->Form->control('contenido');
            echo $this->Form->control('fecha', ['empty' => true]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
