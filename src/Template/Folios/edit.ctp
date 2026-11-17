<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Folio $folio
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3" id="actions-sidebar">
        <ul class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul class="side-nav">
                    <li class="heading"><?= __('Actions') ?></li>
                    <li><?= $this->Form->postLink(
                        __('Delete'),
                        ['action' => 'delete', $folio->id],
                        ['confirm' => __('Are you sure you want to delete # {0}?', $folio->id)]
                        )
                        ?></li>
                    <li><?= $this->Html->link(__('List Folios'), ['action' => 'index']) ?></li>
                </ul>
            </div>
        </ul>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($folio) ?>
    <fieldset>
        <legend><?= __('Edit Folio') ?></legend>
        <?php
        echo $this->Form->input('id_condominio',['type' => 'select',
            'options' => $optionsCondominio,
            'empty' => 'selecciona']);
            echo $this->Form->control('serie');
            echo $this->Form->control('incremento');
            echo $this->Form->control('fecha_alta');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
 </div>
</div>
