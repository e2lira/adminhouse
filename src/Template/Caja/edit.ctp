<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Caja $caja
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $caja->id_gasto],
                ['confirm' => __('Are you sure you want to delete # {0}?', $caja->id_gasto)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Caja'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="caja form large-9 medium-8 columns content">
    <?= $this->Form->create($caja) ?>
    <fieldset>
        <legend><?= __('Edit Caja') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('id_proveedor');
            echo $this->Form->control('id_tipo');
            echo $this->Form->control('id_categoria');
            echo $this->Form->control('fecha_pago', ['empty' => true]);
            echo $this->Form->control('fecha', ['empty' => true]);
            echo $this->Form->control('recibo');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
