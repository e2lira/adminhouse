<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoVisitum $tipoVisitum
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $tipoVisitum->id_tipo],
                ['confirm' => __('Are you sure you want to delete # {0}?', $tipoVisitum->id_tipo)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Tipo Visita'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="tipoVisita form large-9 medium-8 columns content">
    <?= $this->Form->create($tipoVisitum) ?>
    <fieldset>
        <legend><?= __('Edit Tipo Visitum') ?></legend>
        <?php
            echo $this->Form->control('tipo_visita');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
