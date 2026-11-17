<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoPago $tipoPago
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Acciones') ?></li>
        <li><?= $this->Form->postLink(
                __('Eliminar'),
                ['action' => 'delete', $tipoPago->id_tipo],
                ['confirm' => __('Are you sure you want to delete # {0}?', $tipoPago->id_tipo)]
            )
        ?></li>
        <li><?= $this->Html->link(__('Lista de Tipo Pago'), ['action' => 'index', 'id'=>$id_condominio]) ?></li>
    </ul>
</nav>
<div class="tipoPago form large-9 medium-8 columns content">
    <?= $this->Form->create($tipoPago) ?>
    <fieldset>
        <legend><?= __('Editar Tipo Pago') ?></legend>
        <?php
            echo $this->Form->control('nombre');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
