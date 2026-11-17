<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCierre $saldoCierre
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Saldo Cierre'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="saldoCierre form large-9 medium-8 columns content">
    <?= $this->Form->create($saldoCierre) ?>
    <fieldset>
        <legend><?= __('Add Saldo Cierre') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('saldo');
            echo $this->Form->control('fecha', ['empty' => true]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
