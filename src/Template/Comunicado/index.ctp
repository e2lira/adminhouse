<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Comunicado[]|\Cake\Collection\CollectionInterface $comunicado
 */
?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Nuevo Comunicado'), ['controller' => 'Comunicado', 'action' => 'add',
                            'id'=>$id_condominio]) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h2><i class="fa fa-microphone"></i> Comunicados</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover  table-sm" id="tableCond">
                <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('titulo') ?></th>
                <th scope="col"><?= $this->Paginator->sort('comunicado') ?></th>
                <th scope="col"><?= $this->Paginator->sort('adjunto') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comunicado as $comunicado): ?>
            <tr>
                <td><?= h($comunicado['titulo']) ?></td>
                <td><?= h($comunicado['comunicado']) ?></td>

                <td <?php if($comunicado['adjunto'] == ''){echo "style='display:none;'";} ?>>
                    <?= $this->Html->link('Descargar',"/files/Comunicado/adjunto/".h($comunicado['adjunto'])) ?>
                </td>
                <td class="actions">
                    <?= $this->Html->link(__('Ver'), ['action' => 'view', $comunicado['id_comunicado']]) ?>
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $comunicado['id_comunicado']]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $comunicado['id_comunicado']], ['confirm' => __('Are you sure you want to delete # {0}?', $comunicado['id_comunicado'])]) ?>
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
