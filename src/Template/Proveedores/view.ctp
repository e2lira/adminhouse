<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Proveedore $proveedore
 */
?>
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Edit Proveedore'), ['action' => 'edit', $proveedore['id_proveedores']]) ?> </li>
                    <li><?= $this->Form->postLink(__('Delete Proveedore'), ['action' => 'delete', $proveedore['id_proveedores']], ['confirm' => __('Are you sure you want to delete # {0}?', $proveedore['id_proveedores'])]) ?> </li>
                    <li><?= $this->Html->link(__('List Proveedores'), ['action' => 'index']) ?> </li>
                    <li><?= $this->Html->link(__('List Condominio'), ['action' => 'index']) ?> </li>
                </ul>
            </div>
        </div>
    </div>


    <div class="col-md-9">
        <div class="azul">
    <h2><i class="fa fa-truck"></i> <?= h($proveedore['nom_proveedor'])?></h2>
    <table class="table  table-condensed table-responsive">
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= h($proveedore['id_proveedores']) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Condominio') ?></th>
            <td><?= h($proveedore['condominio']) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Rubro') ?></th>
            <td><?= h($proveedore['rubro_proveedor']) ?></td>
        </tr>

        <tr>
            <th scope="row"><?= __('No. Cuenta') ?></th>
            <td><?= h($proveedore['no_cuenta_proveedor']) ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Nota') ?></h4>
        <?= h($proveedore['nota']); ?>
    </div>
 </div>
</div>
