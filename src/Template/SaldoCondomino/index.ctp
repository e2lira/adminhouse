<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SaldoCondomino[]|\Cake\Collection\CollectionInterface $saldoCondomino
 */
?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 ">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Nuevo Saldo Condomino'), ['action' => 'add', 'id'=>$id_condomino]) ?></li>

                </ul>
            </div>
        </div>
    </div>
    <br><br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa fa-wpforms"></i> Saldo inicial del condomino</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="table">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('saldo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('contra') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($saldoCondomino as $saldoCondomino): ?>
            <tr>
                <td><?= $this->Number->format($saldoCondomino['saldo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$',
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldoCondomino['contra'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '-$'
                    ]) ?>
                </td>
                <td><?= $this->Time->format($saldoCondomino['fecha'],'d MMMM Y') ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $saldoCondomino->id_saldo]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $saldoCondomino->id_saldo]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $saldoCondomino->id_saldo], ['confirm' => __('Are you sure you want to delete # {0}?', $saldoCondomino->id_saldo)]) ?>
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
