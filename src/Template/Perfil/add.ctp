<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Perfil $perfil
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Perfil'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="perfil form large-9 medium-8 columns content">
    <?= $this->Form->create($perfil) ?>
    <fieldset>
        <legend><?= __('Add Perfil') ?></legend>
        <?php
            echo $this->Form->control('perfil');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
