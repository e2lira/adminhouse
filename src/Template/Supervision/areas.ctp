<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cuota $cuota
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
                    <li><i class="fa fa-caret-left" style="color: #0056b3;"></i> <?= $this->Html->link(__('Volver'), ['controller' => 'Condominio', 'action' =>
                            'index']) ?>
                    </li>
                    <li><i class="fa fa-plus" style="color: #0056b3;"></i> <?= $this->Html->link(__('Ver reporte'), ['controller' => 'Supervision', 'action' => 'view',
                            'id'=>$id]) ?>
                    </li>
                </ul>
            </div>
        </div>
        <br><br>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($area,['type' => 'file']) ?>
        <fieldset>
            <?php
            echo $this->Form->control('id_supervision', ['value'=>$id, 'type'=>'hidden']);
            echo $this->Form->control('concepto',['placeholder'=>'Ejemplo pasillos']);
            echo $this->Form->control('estado', [
                'type' => 'select',
                'options' => [
                    'Bueno' => 'Bueno',
                    'Regular' => 'Regular',
                    'Malo' => 'Malo'
                ]
            ]);
            echo $this->Form->control('imagen',['type' => 'file']);
            echo $this->Form->control('observaciones');
            ?>
        </fieldset>
        <?= $this->Form->button(__('Submit')) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
