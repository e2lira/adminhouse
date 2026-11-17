<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Excel $excel
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Excel'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="excel form large-9 medium-8 columns content">
    <?= $this->Form->create($excel) ?>
    <fieldset>
        <legend><?= __('Add Excel') ?></legend>
        <?php
            echo $this->Form->control('dir');
            echo $this->Form->control('archivo');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
