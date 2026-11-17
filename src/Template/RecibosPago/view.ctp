<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RecibosPago $recibosPago
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Recibos Pago'), ['action' => 'edit', $recibosPago->id_reporte]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Recibos Pago'), ['action' => 'delete', $recibosPago->id_reporte], ['confirm' => __('Are you sure you want to delete # {0}?', $recibosPago->id_reporte)]) ?> </li>
        <li><?= $this->Html->link(__('List Recibos Pago'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Recibos Pago'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="recibosPago view large-9 medium-8 columns content">
    <h3><?= h($recibosPago->id_reporte) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Reporte') ?></th>
            <td><?= $this->Number->format($recibosPago->id_reporte) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($recibosPago->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($recibosPago->fecha) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Creacion') ?></th>
            <td><?= h($recibosPago->fecha_creacion) ?></td>
        </tr>
    </table>
</div>
