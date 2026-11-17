<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Adeudo $adeudo
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $adeudo->id_adeudo],
                ['confirm' => __('Are you sure you want to delete # {0}?', $adeudo->id_adeudo)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Adeudos'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="adeudos form large-9 medium-8 columns content">
    <?= $this->Form->create($adeudo) ?>
    <fieldset>
        <legend><?= __('Edit Adeudo') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_condomino');
            echo $this->Form->control('mes');
            echo $this->Form->control('monto');
            echo $this->Form->control('concepto');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
