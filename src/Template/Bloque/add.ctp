<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bloque $bloque
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Lista de Cuotas en Bloque'), ['action' => 'index',
                'id'=>$id_condominio]) ?></li>
    </ul>
</nav>
<div class="bloque form large-9 medium-8 columns content">
    <?= $this->Form->create($bloque) ?>
    <fieldset>
        <legend><?= __('Agregar Cuota a Condomino') ?></legend>
        <?php
        echo $this->Form->input('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
        echo $this->Form->control('id_condomino',['type' => 'select',
            'options' => $optionsCondomino,
            'empty' => 'selecciona']);
        echo $this->Form->input('id_tipo',['type' => 'select',
            'options' => $options,
            'empty' => 'selecciona']);
        echo $this->Form->control('cuota');
            echo $this->Form->control('cuota_letra');
            echo $this->Form->control('concepto');
            echo $this->Form->control('fecha');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
<script>
    $( document ).ready(function() {
        // Handler for .ready() called.
        //  alert('hola mundo');
        console.log('Saludos desde jQuery');

        $('#cuota').blur(function(){
            var monto = $(this).val();
            console.log('Valor seleccionado ' + monto);
            var request = $.ajax({
                url: "/cuotas/montoletras",
                method: "get",
                data: { 'monto' :  monto},
                dataType: "json"
            });

            request.done(function( msg ) {
                //var object = JSON.parse(msg);
                //alert("Respuesta: " + msg.cuota );
                //$('#abono').val(msg.cuota);
                $('#cuota-letra').val(msg.monto);
            });

            request.fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });
        });

        //Guardar en adeudos

        $("button").click(function(){
            console.log("Aqui en js");

        });

    });
</script>
