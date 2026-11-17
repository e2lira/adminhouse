<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UsuarioCondomino $usuarioCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $usuarioCondomino->id_pibote],
                ['confirm' => __('Are you sure you want to delete # {0}?', $usuarioCondomino->id_pibote)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Usuario Condomino'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="usuarioCondomino form large-9 medium-8 columns content">
    <?= $this->Form->create($usuarioCondomino) ?>
    <fieldset>
        <legend><?= __('Edit Usuario Condomino') ?></legend>
        <?php
            echo $this->Form->control('id');
            echo $this->Form->control('id_condomino');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
