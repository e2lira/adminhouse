<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Amenidad $amenidad
 */
?>

<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3" id="actions-sidebar">
        <ul class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Amenidades'), ['action' => 'index',
                            'id'=>$id_condominio]) ?></li>
                    <li><?= $this->Html->link(__('Lista de Condominios'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
            </div>
        </ul>
    </div>

    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($amenidad) ?>
    <fieldset>
        <legend><?= __('Agregar Amenidad') ?></legend>
        <?php
        echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
        echo $this->Form->control('amenidad');
        echo $this->Form->control('color',['type' => 'text']);
        echo 'Numero de usuarios';
        echo $this->Form->select(
            'no_usuarios',
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            ['empty' => 'Selecciona']
        );
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>

 </div>
</div>



<script>
    $(document).ready(function () {

        $('#color').minicolors();


    });
</script>
