<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoCuotum $tipoCuotum
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $tipoCuotum->id_tipo],
                ['confirm' => __('Are you sure you want to delete # {0}?', $tipoCuotum->id_tipo)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Tipo Cuota'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="tipoCuota form large-9 medium-8 columns content">
    <?= $this->Form->create($tipoCuotum) ?>
    <fieldset>
        <legend><?= __('Edit Tipo Cuotum') ?></legend>
        <?php
            echo $this->Form->control('tipo_cuota');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
