<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bancarium[]|\Cake\Collection\CollectionInterface $bancaria
 */
?>

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 ">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul class="side-nav">
                    <li><?= $this->Html->link(__('Nueva Cuenta Bancaria'), ['action' => 'add', 'id'=>$id_condominio]) ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-window-maximize"></i> Cuentas Bancarias</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('nombre_banco', ['label'=>'Banco']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('no_cuenta', ['label'=>'Numero de Cuenta']) ?></th>
                <th scope="col"><?= $this->Paginator->sort('estatus') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bancaria as $bancarium): ?>
            <tr>
                <td><?= h($bancarium->nombre_banco) ?></td>
                <td><?= $this->Number->format($bancarium->no_cuenta) ?></td>
                <td><?= h($bancarium->estatus) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Ver'), ['action' => 'view', $bancarium->id_bancaria]) ?>
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $bancarium->id_bancaria]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $bancarium->id_bancaria], ['confirm' => __('Are you sure you want to delete # {0}?', $bancarium->id_bancaria)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
</div>
