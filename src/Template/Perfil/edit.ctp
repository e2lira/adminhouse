<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Perfil $perfil
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $perfil->id_perfil],
                ['confirm' => __('Are you sure you want to delete # {0}?', $perfil->id_perfil)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Perfil'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="perfil form large-9 medium-8 columns content">
    <?= $this->Form->create($perfil) ?>
    <fieldset>
        <legend><?= __('Edit Perfil') ?></legend>
        <?php
            echo $this->Form->control('perfil');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
