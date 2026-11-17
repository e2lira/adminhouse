<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Proveedore $proveedore
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
                        <li><?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $proveedore->id_proveedores],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $proveedore->id_proveedores)]
                            )
                            ?></li>
                        <li><?= $this->Html->link(__('List Proveedores'), ['action' => 'index']) ?></li>
                    <li><?= $this->Html->link (__('List Condominio'), ['action' => 'index']) ?><li>
                </ul>
            </div>
        </div>
    </div>


    <br><br><br>

    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($proveedore) ?>
    <fieldset>
        <legend><?= __('Edit Proveedore') ?></legend>
        <?php
            echo $this->Form->control('nom_proveedor');
            echo $this->Form->control('no_cuenta_proveedor');
            echo $this->Form->control('rubro_proveedor');
            echo $this->Form->control('nota');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
 </div>
</div>
