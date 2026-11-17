<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condomino $condomino
 */
?>

<div class="row">
    <div class="col-md-2">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Condomino'), ['action' => 'edit', $condomino->id_condomino]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Condomino'), ['action' => 'delete', $condomino->id_condomino], ['confirm' => __('Are you sure you want to delete # {0}?', $condomino->id_condomino)]) ?> </li>
        <li><?= $this->Html->link(__('List Condomino'), ['action' => 'index', $condomino->id_condominio]) ?> </li>
        <li><?= $this->Html->link(__('New Condomino'), ['action' => 'add',$condomino->id_condominio]) ?> </li>
    </ul>
  </div>
 </div>
        <br>
        <br>
</div>

    <div class="col-md-10">
        <div class="azul">

            <h2><i class="fa fa-user-circle"></i> <?= h($condomino->numero_departamento)?></h2>
            <table class="table  table-condensed table-responsive">
        <tr>
            <th scope="row"><?= __('Residente Condominio') ?></th>
            <td><?= h($condomino->residente_condominio) ?></td>
        </tr>
                <tr>
                    <th scope="row"><?= __('Saldo Inicial') ?></th>
                    <td><?= h($condomino->saldo_inicial) ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= __('Cuota') ?></th>
                    <td><?= h($condomino->cuota) ?></td>
                </tr>
        <tr>
            <th scope="row"><?= __('Numero Contacto') ?></th>
            <td><?= h($condomino->numero_contacto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Correo Contacto') ?></th>
            <td><?= h($condomino->correo_contacto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Arrendatario') ?></th>
            <td><?= h($condomino->arrendatario) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Telefono Arrendatario') ?></th>
            <td><?= h($condomino->telefono_arrendatario) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Marca Auto') ?></th>
            <td><?= h($condomino->marca_auto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Modelo Auto') ?></th>
            <td><?= h($condomino->modelo_auto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Color Auto') ?></th>
            <td><?= h($condomino->color_auto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Placas Auto') ?></th>
            <td><?= h($condomino->placas_auto) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Condomino') ?></th>
            <td><?= $this->Number->format($condomino->id_condomino) ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Nota') ?></h4>
        <?= $this->Text->autoParagraph(h($condomino->nota)); ?>
    </div>
  </div>
</div>
</div>
