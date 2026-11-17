<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Reglamento $reglamento
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3" id="actions-sidebar">
        <ul class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Reglamentos'), ['action' => 'index',
                            'id'=>$id_condominio]) ?></li>
                </ul>
            </div>
        </ul>
    </div>
<div class="reglamento form large-9 medium-8 columns content">
    <?= $this->Form->create($reglamento) ?>
    <fieldset>
        <legend><?= __('Agregar Reglamento') ?></legend>
        <?php
        echo $this->Form->input('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
            echo $this->Form->control('nombre');
        echo $this->Form->control('reglamento');
        echo $this->CKEditor->replace('reglamento');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Guardar')) ?>
    <?= $this->Form->end() ?>
</div>
