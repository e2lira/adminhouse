<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Favor $favor
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $favor->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $favor->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Favor'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="favor form large-9 medium-8 columns content">
    <?= $this->Form->create($favor) ?>
    <fieldset>
        <legend><?= __('Edit Favor') ?></legend>
        <?php
            echo $this->Form->control('id_pago');
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_condomino');
            echo $this->Form->control('abono');
            echo $this->Form->control('fecha', ['empty' => true]);
            echo $this->Form->control('estatus');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
