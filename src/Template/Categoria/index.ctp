<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TipoGasto[]|\Cake\Collection\CollectionInterface $tipoGasto
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
                    <li><?= $this->Html->link(__('Nueva Categoria'), ['action' => 'add', 'id'=>$id_condominio]) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <br><br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <div class="page-header">
            <h2><i class="fa-solid fa-bars"></i> Categorias</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tablePro">
                <thead>
                <tr>
                    <th scope="col"><?= $this->Paginator->sort('id_categoria') ?></th>
                    <th scope="col"><?= $this->Paginator->sort('categoria') ?></th>
                    <th scope="col" class="actions"><?= __('Actions') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($categoria as $categorium): ?>
                    <tr>
                        <td><?= $this->Number->format($categorium->id_categoria) ?></td>
                        <td><?= h($categorium->categoria) ?></td>
                        <td class="actions">
                            <?= $this->Html->link(__('View'), ['action' => 'view', $categorium->id_categoria]) ?>
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $categorium->id_categoria]) ?>
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $categorium->id_categoria], ['confirm' => __('Are you sure you want to delete # {0}?', $categorium->id_categoria)]) ?>
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
