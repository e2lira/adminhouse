<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Respuesta[]|\Cake\Collection\CollectionInterface $respuestas
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Respuesta'), ['action' => 'add']) ?></li>
    </ul>
</nav>
<div class="respuestas index large-9 medium-8 columns content">
    <h3><?= __('Respuestas') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_respuestas') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_mensaje') ?></th>
                <th scope="col"><?= $this->Paginator->sort('mensaje') ?></th>
                <th scope="col"><?= $this->Paginator->sort('dir') ?></th>
                <th scope="col"><?= $this->Paginator->sort('adjunto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condominio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condomino') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($respuestas as $respuesta): ?>
            <tr>
                <td><?= $this->Number->format($respuesta->id_respuestas) ?></td>
                <td><?= $this->Number->format($respuesta->id_mensaje) ?></td>
                <td><?= h($respuesta->mensaje) ?></td>
                <td><?= h($respuesta->dir) ?></td>
                <td><?= h($respuesta->adjunto) ?></td>
                <td><?= $this->Number->format($respuesta->id_condominio) ?></td>
                <td><?= $this->Number->format($respuesta->id_condomino) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $respuesta->id_respuestas]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $respuesta->id_respuestas]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $respuesta->id_respuestas], ['confirm' => __('Are you sure you want to delete # {0}?', $respuesta->id_respuestas)]) ?>
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
