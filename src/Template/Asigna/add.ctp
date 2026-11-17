<div class="jumbotron">
    <h1 class="display-4">Usuario + Condomino</h1>
    <p class="lead">En el siguiente recuadro podras asignar un condomino a un usuario</p>
    <hr class="my-4">
    <p class="lead">

    </p>
</div>
<br>

<div class="asigna form large-9 medium-8 columns content">
    <?= $this->Form->create($asigna) ?>
    <fieldset>
        <?php
        //echo $this->Form->control('id_user',['label'=>'Usuario']);
        echo $this->Form->input('id_user',['label'=>'Usuario','type' => 'select',
            'options' => $users,
            'empty' => 'selecciona']);
        echo $this->Form->control('id_condomino',['type' => 'hidden', 'value'=>$condomino['id_condomino']]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Guardar')) ?>
    <?= $this->Form->end() ?>
</div>
