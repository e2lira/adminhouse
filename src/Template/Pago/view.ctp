<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Pago $pago
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
                    <li class="heading"><?= __('Acciones') ?></li>
                    <li><?= $this->Html->link(__('Editar Pago'), ['action' => 'edit', $pago->id_pago]) ?> </li>
                    <li><?= $this->Html->link(__('Lista de Pagos'), ['action' => 'index']) ?> </li>
                </ul>
            </div>
        </div>
        <br><br>
    </div>
<br><br>
    <div class="col-md-10">
        <div class="azul">
            <h2><i class="fa fa-credit-card"></i> <?= h($pago->referencia)?></h2>
            <table class="table  table-condensed table-responsive">
        <tr>
            <th scope="row"><?= __('Monto Letra') ?></th>
            <td><?= h($pago->monto_letra) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Concepto') ?></th>
            <td><?= h($pago->concepto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Referencia') ?></th>
            <td><?= h($pago->referencia) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Pago') ?></th>
            <td><?= $this->Number->format($pago->id_pago) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($pago->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condomino') ?></th>
            <td><?= $this->Number->format($pago->id_condomino) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Abono') ?></th>
            <td><?= $this->Number->format($pago->abono) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Pago') ?></th>
            <td><?= h($pago->fecha_pago) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha') ?></th>
            <td><?= h($pago->fecha) ?></td>
        </tr>
    </table>
  </div>
</div>
