<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCondomino $saldoCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $saldoCondomino->id_saldo],
                ['confirm' => __('Are you sure you want to delete # {0}?', $saldoCondomino->id_saldo)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Saldo Condomino'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="saldoCondomino form large-9 medium-8 columns content">
    <?= $this->Form->create($saldoCondomino) ?>
    <fieldset>
        <legend><?= __('Edit Saldo Condomino') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_condomino');
            echo $this->Form->control('saldo');
            echo $this->Form->control('fecha');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
