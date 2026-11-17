<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Reglamento $reglamento
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $reglamento->id_reglamento],
                ['confirm' => __('Are you sure you want to delete # {0}?', $reglamento->id_reglamento)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Reglamento'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="reglamento form large-9 medium-8 columns content">
    <?= $this->Form->create($reglamento) ?>
    <fieldset>
        <legend><?= __('Edit Reglamento') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('nombre');
            echo $this->Form->control('reglamento');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
