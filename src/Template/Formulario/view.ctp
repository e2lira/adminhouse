<?php
	echo $this->Html->script('SweetAlertHelper.sweetalert2.min');

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Formulario $formulario
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Formulario'), ['action' => 'edit', $formulario->id_formulario]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Formulario'), ['action' => 'delete', $formulario->id_formulario], ['confirm' => __('Are you sure you want to delete # {0}?', $formulario->id_formulario)]) ?> </li>
        <li><?= $this->Html->link(__('List Formulario'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Formulario'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="formulario view large-9 medium-8 columns content">
    <h3><?= h($formulario->id_formulario) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Nombre Elaboro') ?></th>
            <td><?= h($formulario->nombre_elaboro) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Estancia') ?></th>
            <td><?= h($formulario->imagen_estancia) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Escalera') ?></th>
            <td><?= h($formulario->imagen_escalera) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Pasillo') ?></th>
            <td><?= h($formulario->imagen_pasillo) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Planta Luz') ?></th>
            <td><?= h($formulario->imagen_planta_luz) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Cisterna') ?></th>
            <td><?= h($formulario->imagen_cisterna) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Observaciones Cisterna') ?></th>
            <td><?= h($formulario->observaciones_cisterna) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Bomba') ?></th>
            <td><?= h($formulario->imagen_bomba) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Estacionamiento') ?></th>
            <td><?= h($formulario->imagen_estacionamiento) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Caldera') ?></th>
            <td><?= h($formulario->imagen_caldera) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Gimnasio') ?></th>
            <td><?= h($formulario->imagen_gimnasio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Observaciones Gimnasio') ?></th>
            <td><?= h($formulario->observaciones_gimnasio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Salon') ?></th>
            <td><?= h($formulario->imagen_salon) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Imagen Roof') ?></th>
            <td><?= h($formulario->imagen_roof) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Formulario') ?></th>
            <td><?= $this->Number->format($formulario->id_formulario) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($formulario->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Fecha Creacion') ?></th>
            <td><?= h($formulario->fecha_creacion) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Estancia') ?></th>
            <td><?= $formulario->estancia ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Escalera') ?></th>
            <td><?= $formulario->escalera ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Pasillo') ?></th>
            <td><?= $formulario->pasillo ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Elevadores') ?></th>
            <td><?= $formulario->elevadores ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Planta Luz') ?></th>
            <td><?= $formulario->planta_luz ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Cisterna') ?></th>
            <td><?= $formulario->cisterna ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Bomba') ?></th>
            <td><?= $formulario->bomba ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Estacionamiento') ?></th>
            <td><?= $formulario->estacionamiento ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Caldera') ?></th>
            <td><?= $formulario->caldera ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Gimnasio') ?></th>
            <td><?= $formulario->gimnasio ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Salon') ?></th>
            <td><?= $formulario->salon ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Roof') ?></th>
            <td><?= $formulario->roof ? __('Yes') : __('No'); ?></td>
        </tr>
    </table>
    <div class="row">
        <h4><?= __('Observaciones Estancia') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_estancia)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Escalera') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_escalera)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Pasillo') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_pasillo)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Elevadores') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_elevadores)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Planta Luz') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_planta_luz)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Bomba') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_bomba)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Estacionamiento') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_estacionamiento)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Caldera') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_caldera)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Salon') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_salon)); ?>
    </div>
    <div class="row">
        <h4><?= __('Observaciones Roof') ?></h4>
        <?= $this->Text->autoParagraph(h($formulario->observaciones_roof)); ?>
    </div>
</div>
