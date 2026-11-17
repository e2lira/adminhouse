<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCondominio $saldoCondominio
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Saldo Inicial'), ['action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
                </ul>
            </div>
        </div>
        <br><br>
    </div>

    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($saldoCondominio) ?>
    <fieldset>
        <legend><?= __('Agregar Saldo Inicial del  Condominio') ?></legend>
        <?php
            echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
            echo $this->Form->control('saldo');
            echo $this->Form->control('fecha');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Guardar')) ?>
    <?= $this->Form->end() ?>
</div>
