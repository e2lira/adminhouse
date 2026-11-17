<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bancarium $bancarium
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $bancarium->id_bancaria],
                ['confirm' => __('Are you sure you want to delete # {0}?', $bancarium->id_bancaria)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Bancaria'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="bancaria form large-9 medium-8 columns content">
    <?= $this->Form->create($bancarium) ?>
    <fieldset>
        <legend><?= __('Edit Bancarium') ?></legend>
        <?php
            echo $this->Form->control('nombre_banco');
            echo $this->Form->control('no_cuenta');
        echo $this->Form->control('estatus', [
        'options' => ['activo' => 'Activo', 'inactivo' => 'Inactivo']]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
