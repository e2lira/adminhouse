<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RecibosPago $recibosPago
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $recibosPago->id_reporte],
                ['confirm' => __('Are you sure you want to delete # {0}?', $recibosPago->id_reporte)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Recibos Pago'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="recibosPago form large-9 medium-8 columns content">
    <?= $this->Form->create($recibosPago) ?>
    <fieldset>
        <legend><?= __('Edit Recibos Pago') ?></legend>
        <?php
            echo $this->Form->control('id_condominio');
            echo $this->Form->control('fecha', ['empty' => true]);
            echo $this->Form->control('fecha_creacion', ['empty' => true]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
