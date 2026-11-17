<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cuota $cuota
 */
?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Condominios'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
                    <br>
                    <br>

                </ul>
            </div>
        </div>

        <br><br>
    </div>


    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($indiviso) ?>
        <fieldset>
            <legend><?= __('Edit Indiviso') ?></legend>
            <?php
            echo $this->Form->input('id_tipo',['type' => 'select',
                'options' => $optionCuota,
                'empty' => 'selecciona']);
            echo $this->Form->control('cuota');
            echo $this->Form->control('cuota_letra');
            echo $this->Form->control('concepto');
            echo $this->Form->control('fecha', ['empty' => true]);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Submit')) ?>
        <?= $this->Form->end() ?>
    </div>
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



    });
</script>
