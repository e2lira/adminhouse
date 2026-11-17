<?php
	echo $this->Html->script('SweetAlertHelper.sweetalert2.min');

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Formulario[]|\Cake\Collection\CollectionInterface $formulario
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Formulario'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="formulario index large-9 medium-8 columns content">
    <h3><?= __('Formulario') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_formulario') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha_creacion') ?></th>
                <th scope="col"><?= $this->Paginator->sort('nombre_elaboro') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($formulario as $formulario): ?>
            <tr>
                <td><?= $this->Number->format($formulario->id_formulario) ?></td>
                <td><?= h($formulario->fecha_creacion) ?></td>
                <td><?= h($formulario->nombre_elaboro) ?></td>

                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $formulario->id_formulario]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $formulario->id_formulario]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $formulario->id_formulario], ['confirm' => __('Are you sure you want to delete # {0}?', $formulario->id_formulario)]) ?>
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
