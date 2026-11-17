<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asigna $asigna
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $asigna->id_asigna],
                ['confirm' => __('Are you sure you want to delete # {0}?', $asigna->id_asigna)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Asigna'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="asigna form large-9 medium-8 columns content">
    <?= $this->Form->create($asigna) ?>
    <fieldset>
        <legend><?= __('Edit Asigna') ?></legend>
        <?php
            echo $this->Form->control('id_user');
            echo $this->Form->control('id_condomino');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
