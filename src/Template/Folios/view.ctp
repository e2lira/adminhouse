<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Folio $folio
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
                    <li><?= $this->Html->link(__('Edit Folio'), ['action' => 'edit', $folio->id]) ?> </li>
                    <li><?= $this->Form->postLink(__('Delete Folio'), ['action' => 'delete', $folio->id], ['confirm' => __('Are you sure you want to delete # {0}?', $folio->id)]) ?> </li>
                    <li><?= $this->Html->link(__('List Folios'), ['action' => 'index']) ?> </li>
                    <li><?= $this->Html->link(__('New Folio'), ['action' => 'add']) ?> </li>
                </ul>
            </div>
        </div>
        <br>
        <br>
    </div>

    <div class="col-md-10">
        <div class="azul">

            <h2><i class="fa fa-user-circle"></i> <?= h($folio->serie)?></h2>
            <table class="table  table-condensed table-responsive">
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($folio->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($folio->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Serie') ?></th>
            <td><?= $this->Number->format($folio->serie) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Incremento') ?></th>
            <td><?= $this->Number->format($folio->incremento) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Alta') ?></th>
            <td><?= h($folio->fecha_alta) ?></td>
        </tr>
    </table>
 </div>
</div>
