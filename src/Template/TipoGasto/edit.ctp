<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoGasto $tipoGasto
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Eliminar'),
                ['action' => 'delete', $tipoGasto->id_tipo],
                ['confirm' => __('Are you sure you want to delete # {0}?', $tipoGasto->id_tipo)]
            )
        ?></li>
        <li><?= $this->Html->link(__('Lista de Tipo Gasto'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="tipoGasto form large-9 medium-8 columns content">
    <?= $this->Form->create($tipoGasto) ?>
    <fieldset>
        <legend><?= __('Edit Tipo Gasto') ?></legend>
        <?php
            echo $this->Form->control('tipo_gasto');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
