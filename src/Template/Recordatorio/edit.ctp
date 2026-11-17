<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Recordatorio $recordatorio
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $recordatorio->id_recordatorio],
                ['confirm' => __('Are you sure you want to delete # {0}?', $recordatorio->id_recordatorio)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Recordatorio'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="recordatorio form large-9 medium-8 columns content">
    <?= $this->Form->create($recordatorio) ?>
    <fieldset>
        <legend><?= __('Edit Recordatorio') ?></legend>
        <?php
            echo $this->Form->control('destinatarios');
            echo $this->Form->control('asunto');
            echo $this->Form->control('mensaje');
            echo $this->Form->control('adjunto');
            echo $this->Form->control('dir');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
