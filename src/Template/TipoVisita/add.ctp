<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoVisitum $tipoVisitum
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Tipo Visita'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="tipoVisita form large-9 medium-8 columns content">
    <?= $this->Form->create($tipoVisitum) ?>
    <fieldset>
        <legend><?= __('Add Tipo Visitum') ?></legend>
        <?php
            echo $this->Form->control('tipo_visita');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
