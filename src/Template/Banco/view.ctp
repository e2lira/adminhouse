<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Banco $banco
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Banco'), ['action' => 'index']) ?> </li>
    </ul>
</nav>
<div class="banco view large-9 medium-8 columns content">
    <h3><?= h($banco->id_movimiento) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Id Movimiento') ?></th>
            <td><?= $this->Number->format($banco->id_movimiento) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Pago') ?></th>
            <td><?= $this->Number->format($banco->id_pago) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Cargo') ?></th>
            <td><?= $this->Number->format($banco->id_cargo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($banco->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cargo') ?></th>
            <td><?= $this->Number->format($banco->cargo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Abono') ?></th>
            <td><?= $this->Number->format($banco->abono) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($banco->fecha) ?></td>
        </tr>
    </table>
</div>
