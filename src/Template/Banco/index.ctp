<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Banco[]|\Cake\Collection\CollectionInterface $banco
 */
 echo $this->Html->script('SweetAlertHelper.sweetalert2.min');
?>
<div class="row">
<div class="col-md-12">
    <h3><i class="fa fa-university"></i>Banco</h3>
    <div class="table-responsive">
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_condominio', ['Condominio']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('cargo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('abono') ?></th>
                <th scope="col"><?= $this->Paginator->sort('nombre_banco') ?></th>
                <th scope="col"><?= $this->Paginator->sort('no_cuenta') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($banco as $banco): ?>
            <tr>
                <td><?= h($banco['condominio']) ?></td>
                <td><?= h($banco['cargo']) ?></td>
                <td><?= h($banco['abono']) ?></td>
                <td><?= h($banco['nombre_banco']) ?></td>
                <td><?= h($banco['no_cuenta']) ?></td>
                <td><?= h($banco['fecha']) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $banco['id_movimiento']]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
  </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
    </div>
  </div>
</div>