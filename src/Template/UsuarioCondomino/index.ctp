<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UsuarioCondomino[]|\Cake\Collection\CollectionInterface $usuarioCondomino
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Usuario Condomino'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="usuarioCondomino index large-9 medium-8 columns content">
    <h3><?= __('Usuario Condomino') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_pibote') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condomino') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarioCondomino as $usuarioCondomino): ?>
            <tr>
                <td><?= $this->Number->format($usuarioCondomino->id_pibote) ?></td>
                <td><?= $this->Number->format($usuarioCondomino->id) ?></td>
                <td><?= $this->Number->format($usuarioCondomino->id_condomino) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $usuarioCondomino->id_pibote]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $usuarioCondomino->id_pibote]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $usuarioCondomino->id_pibote], ['confirm' => __('Are you sure you want to delete # {0}?', $usuarioCondomino->id_pibote)]) ?>
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
