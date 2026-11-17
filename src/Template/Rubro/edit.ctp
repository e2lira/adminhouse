<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Rubro $rubro
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $rubro->id_rubro],
                ['confirm' => __('Are you sure you want to delete # {0}?', $rubro->id_rubro)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Rubro'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="rubro form large-9 medium-8 columns content">
    <?= $this->Form->create($rubro) ?>
    <fieldset>
        <legend><?= __('Edit Rubro') ?></legend>
        <?php
            echo $this->Form->control('rubro');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
