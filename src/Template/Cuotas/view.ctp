<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cuota $cuota
 */
?>
<div class="row">
    <div class="col-md-2">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul class="side-nav">
                    <li><?= $this->Html->link(__('Editar Cuota'), ['action' => 'edit', $cuota->id_cuotas]) ?> </li>
                    <li><?= $this->Form->postLink(__('Eliminar Cuota'), ['action' => 'delete', $cuota->id_cuotas], ['confirm' => __('Are you sure you want to delete # {0}?', $cuota->id_cuotas)]) ?> </li>
                </ul>
            </div>
        </div>
        <br>
        <br>
    </div>
    <div class="col-md-10">
        <div class="azul">

            <h2><i class="fa fa-user-circle"></i> <?= h($cuota->cuota)?></h2>
            <table class="table  table-condensed table-responsive">        <tr>
            <th scope="row"><?= __('Cuota Letra') ?></th>
            <td><?= h($cuota->cuota_letra) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Cuotas') ?></th>
            <td><?= $this->Number->format($cuota->id_cuotas) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($cuota->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($cuota->id_condomino) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Tipo') ?></th>
            <td><?= $this->Number->format($cuota->id_tipo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cuota') ?></th>
            <td><?= $this->Number->format($cuota->cuota) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Cargo') ?></th>
            <td><?= h($cuota->fecha_cargo) ?></td>
        </tr>
    </table>
 </div>
</div>
