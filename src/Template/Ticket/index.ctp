<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ticket[]|\Cake\Collection\CollectionInterface $ticket
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
                    <li><?= $this->Html->link(__('Regresar al menú'), ['controller' => 'Condominio', 'action' => 'index']) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <br>
    <div class="col-xs-12 col-sm-9 col-md-9 ">
    <h2><i class="fa fa-bell-concierge"></i> Ticket</h2>
    <div class="table-responsive">
        <table class="table table-striped table-hover  table-sm" id="tableCond">
            <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('nombre') ?></th>
                <th scope="col"><?= $this->Paginator->sort('prioridad') ?></th>
                <th scope="col"><?= $this->Paginator->sort('asunto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('adjunto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('estatus') ?></th>
                <th scope="col" class="actions"><?= __('Acciones') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ticket as $ticket): ?>
            <tr>
                <td><?= h($ticket->nombre) ?></td>
                <td><?= h($ticket->prioridad) ?></td>
                <td><?= h($ticket->asunto) ?></td>
                <td <?php if($ticket['adjunto'] == ''){echo "style='display:none;'";} ?>>
                    <?= $this->Html->link('Descargar',"/webroot/files/Ticket/adjunto/".h($ticket['adjunto'])) ?>
                </td>
                <td><?= h($ticket->estatus) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Ver'), ['action' => 'view', $ticket->id_ticket]) ?>
                    <?= $this->Html->link(__('Estatus'), ['action' => 'edit', $ticket->id_ticket]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $ticket->id_ticket], ['confirm' => __('Are you sure you want to delete # {0}?', $ticket->id_ticket)]) ?>
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
