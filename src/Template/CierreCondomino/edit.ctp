<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CierreCondomino $cierreCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $cierreCondomino->id_cierre],
                ['confirm' => __('Are you sure you want to delete # {0}?', $cierreCondomino->id_cierre)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Cierre Condomino'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="cierreCondomino form large-9 medium-8 columns content">
    <?= $this->Form->create($cierreCondomino) ?>
    <fieldset>
        <legend><?= __('Edit Cierre Condomino') ?></legend>
        <?php
            echo $this->Form->control('id_condomino');
            echo $this->Form->control('saldo');
            echo $this->Form->control('fecha');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
