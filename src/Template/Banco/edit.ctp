<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Banco $banco
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $banco->id_movimiento],
                ['confirm' => __('Are you sure you want to delete # {0}?', $banco->id_movimiento)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Banco'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="banco form large-9 medium-8 columns content">
    <?= $this->Form->create($banco) ?>
    <fieldset>
        <legend><?= __('Edit Banco') ?></legend>
        <?php
            echo $this->Form->control('id_pago');
            echo $this->Form->control('id_cargo');
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('cargo');
            echo $this->Form->control('abono');
            echo $this->Form->control('fecha', ['empty' => true]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
