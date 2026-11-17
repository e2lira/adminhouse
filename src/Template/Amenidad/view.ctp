<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Amenidad $amenidad
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
                    <li><?= $this->Html->link(__('Edit Amenidad'), ['action' => 'edit', $amenidad->id_amenidad]) ?> </li>
                    <li><?= $this->Form->postLink(__('Delete Amenidad'), ['action' => 'delete', $amenidad->id_amenidad], ['confirm' => __('Are you sure you want to delete # {0}?', $amenidad->id_amenidad)]) ?> </li>
                    <li><?= $this->Html->link(__('List Amenidad'), ['action' => 'index']) ?> </li>
                    <li><?= $this->Html->link(__('New Amenidad'), ['action' => 'add']) ?> </li>
                    <li><?= $this->Html->link(__('List Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?> </li>
                </ul>
            </div>
        </div>
        <br>
        <br>
    </div>


    <div class="col-md-10">
        <div class="azul">

            <h2><i class="fa fa-book"></i> <?= h($amenidad->amenidad)?></h2>
            <table class="table  table-condensed table-responsive">
                <tr>
                    <th scope="row"><?= __('Color') ?></th>
                    <td style="color: <?= h($amenidad->color) ?>;"><?= h($amenidad->color) ?></td>
                </tr>
        <tr>
            <th scope="row"><?= __('Amenidad') ?></th>
            <td><?= h($amenidad->amenidad) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Amenidad') ?></th>
            <td><?= $this->Number->format($amenidad->id_amenidad) ?></td>
        </tr>
    </table>
 </div>
</div>
