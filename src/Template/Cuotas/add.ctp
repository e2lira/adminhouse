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
                    <li><?= $this->Html->link(__('Lista de Condominos'), ['controller' => 'Condomino', 'action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                    <li><?= $this->Html->link(__('Lista de Condominios'), ['controller' => 'Condominio', 'action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                    <li><?= $this->Html->link(__('Lista de Cuotas'), ['action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                    <br>
                    <br>
                    <li style="background-color: #c4e3f3; color: white;"><?= $this->Html->link(__('Cuota Indiviso'), ['controller' => 'indiviso', 'action' => 'add', 'id'=>$condominio['id_condominio']]) ?></li>
                    <br>
                    <!-- <li style="background-color: #c4e3f3; color: white;"><?= $this->Html->link(__('Cargar Excel'), ['action' => 'excel', 'id'=>$condominio['id_condominio']]) ?></li> -->
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="container">
                <h3>Condominio: <?= $condominio->nombre_condominio ?></h3>
                <ul style="color: #0f0f0f">
                    <li><span>Nombre:</span> <?= $condominio->nombre_condominio ?></li>
                    <li><span>Direccion:</span> <?= $condominio->direccion_condominio ?></li>
                    <li><span>Telefono:</span> <?= $condominio->telefono_condominio ?></li>
                    <li><span>No. Cuenta:</span> <?= $condominio->no_cuenta_condominio ?></li>
                    <li><span>Banco:</span> <?= $condominio->banco_cuenta ?></li>
                    <br>
                </ul>
            </div>
        </div>
        <br><br>
    </div>


    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($cuota) ?>
        <fieldset>
            <legend><?= __('Agregar Cuota General') ?></legend>
            <?php
                echo $this->Form->input('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
                echo $this->Form->input('id_tipo',['type' => 'select',
                'options' => $options,
                'empty' => 'selecciona']);
                echo $this->Form->control('cuota');
                echo $this->Form->control('cuota_letra');
                echo $this->Form->control('concepto');
                echo $this->Form->control('fecha');
            ?>
        </fieldset>
        <?= $this->Form->button(__('Guardar')) ?>
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
