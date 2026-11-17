<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Visita[]|\Cake\Collection\CollectionInterface $visitas
 */
?>
<div class="row">
<div class="col-md-3 col-sm-12" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Nueva Visita'), ['action' => 'add', 'id'=>$id_condominio]) ?></li>
    </ul>
</div>
<div class="col-md-9 col-sm-12">
    <h3><?= __('Visitas') ?></h3>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('nombre') ?></th>
                <th scope="col"><?= $this->Paginator->sort('departamento') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col"><?= $this->Paginator->sort('hora') ?></th>
                <th scope="col"><?= $this->Paginator->sort('tipo_visita') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($visitas as $visita): ?>
            <tr>
                <td><?= h($visita->nombre) ?></td>
                <td><?= h($visita->departamento) ?></td>
                <td><?= h($visita->fecha) ?></td>
                <td><?= h($visita->hora) ?></td>
                <td><?= h($visita->tipo_visita) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Qr'), ['action' => 'view', $visita->id_visita]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $visita->id_visita], ['confirm' => __('Are you sure you want to delete # {0}?', $visita->id_visita)]) ?>
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
