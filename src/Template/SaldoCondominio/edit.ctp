<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCondominio $saldoCondominio
 */
?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 ">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $saldoCondominio->id_saldo],
                ['confirm' => __('Are you sure you want to delete # {0}?', $saldoCondominio->id_saldo)]
            )
        ?></li>
        <li><?= $this->Html->link(__('Volver al menu'), ['controller'=>'condominio','action' => 'index']) ?></li>
    </ul>
</div>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
    <?= $this->Form->create($saldoCondominio) ?>
    <fieldset>
        <legend><?= __('Editar Saldo Condominio') ?></legend>
        <?php
            echo $this->Form->control('saldo');
            echo $this->Form->control('fecha');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
