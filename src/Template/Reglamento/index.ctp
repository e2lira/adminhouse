<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Reglamento[]|\Cake\Collection\CollectionInterface $reglamento
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Reglamento'), ['action' => 'add',
                'id'=>$id_condominio]) ?></li>
    </ul>
</nav>
<div class="reglamento index large-9 medium-8 columns content">
    <h3><?= __('Reglamento') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id_reglamento') ?></th>
                <th scope="col"><?= $this->Paginator->sort('id_condominio') ?></th>
                <th scope="col"><?= $this->Paginator->sort('nombre') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reglamento as $reglamento): ?>
            <tr>
                <td><?= $this->Number->format($reglamento->id_reglamento) ?></td>
                <td><?= $this->Number->format($reglamento->id_condominio) ?></td>
                <td><?= h($reglamento->nombre) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $reglamento->id_reglamento]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $reglamento->id_reglamento]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $reglamento->id_reglamento], ['confirm' => __('Are you sure you want to delete # {0}?', $reglamento->id_reglamento)]) ?>
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
