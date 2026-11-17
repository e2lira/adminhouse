<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Concepto[]|\Cake\Collection\CollectionInterface $conceptos
 */
?>
<?= $this->Flash->render() ?>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 ">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul class="side-nav">
                    <li><?= $this->Html->link(__('Lista de condominios'), ['controller'=>'condominio','action' => 'index']) ?></li>
                    <br>
                    <li><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                            Nuevo concepto
                        </button></li>
                </ul>

            </div>

        </div>
        <br>
        <div class="card">
            <div class="container">
            </div>
            <div>
                <ul class="side-nav">
                    <br>
                    <h4 style="color: #6c757d;">Total: $<?= $query2['sum']?></h4>

                </ul>

            </div>

        </div>
    </div>
<div class="col-xs-12 col-sm-12 col-md-9 ">
    <h2> Conceptos</h2>
    <br>
    <div class="table-responsive">
        <table class="table table-striped table-hover  table-sm" id="table">
            <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('concepto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('gasto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $lastConcepto = null; ?>
            <?php foreach ($conceptos as $row): ?>
            <?php $lastConcepto = $row; ?>
            <tr>
                <td><?= h($row->concept) ?></td>
                <td><?= h($row->gasto) ?></td>
                <td><?= $this->Time->format($row['fecha'],'d MMMM Y') ?></td>

                <td class="actions">
                    <?= $this->Html->link(__('Editar'), ['action' => 'edit', $row->id_conceptos]) ?>
                    <?= $this->Form->postLink(__('Eliminar'), ['action' => 'delete', $row->id_conceptos], ['confirm' => __('Are you sure you want to delete # {0}?', $row->id_conceptos)]) ?>
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
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Agregar Concepto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 col-sm-12 col-xs-12 col-lg-12">
                        <?= $this->Form->create($concepto) ?>
                        <fieldset>
                            <?php
                            // With rows, keep using the last listed concept; with none, use the caja record.
                            echo $this->Form->control('id_gasto',['type' => 'hidden', 'value'=>$lastConcepto ? $lastConcepto['id_gasto'] : $formGastoId]);
                            echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$lastConcepto ? $lastConcepto['id_condominio'] : $formCondominioId]);
                            echo $this->Form->control('concept',['label'=>'Concepto']);
                            echo $this->Form->control('gasto');
                            echo $this->Form->control('fecha');
                            ?>
                        </fieldset>
                        <?= $this->Form->button(__('Submit')) ?>
                        <?= $this->Form->end() ?>
                    </div>                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
