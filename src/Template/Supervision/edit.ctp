<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Supervision $supervision
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $supervision->id_supervision],
                ['confirm' => __('Are you sure you want to delete # {0}?', $supervision->id_supervision)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Supervision'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="supervision form large-9 medium-8 columns content">
    <?= $this->Form->create($supervision) ?>
    <fieldset>
        <legend><?= __('Edit Supervision') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('fecha');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
