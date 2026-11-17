<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Gasto $gasto
 */
echo $this->Html->script('jquery.MultiFile.js');
?>
<?= $this->Html->script('ShowImagePreview') ?>

<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Condominios'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
                    <li><?= $this->Html->link(__('Lista de Egresos'), ['controller' => 'Gasto', 'action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                    <br>
                    <li style="background-color: #c4e3f3; color: white;"><?= $this->Html->link(__('Caja chica'), ['controller' => 'caja', 'action' => 'add', 'id'=>$condominio['id_condominio']]) ?></li>

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
                </ul>
            </div>
        </div>
        <br><br>
    </div>

    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <div class="page-header">
            <?= $this->Form->create($gasto,['type' => 'file']) ?>

            <fieldset>
                <legend><?= __('Agregar Egreso') ?></legend>
                <?php
                echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);

                echo $this->Form->control('id_tipo',['type' => 'select',
                'options' => $options,
                'empty' => 'selecciona']);

                echo $this->Form->control('id_proveedores',['type' => 'select',
                'options' => $optionProveedor,
                'empty' => 'selecciona']);

                echo $this->Form->control('id_categoria',['type' => 'select',
                'options' => $optionCategoria,
                'empty' => 'selecciona']);

                echo $this->Form->control('gasto');
                echo $this->Form->control('monto_letra');
                echo $this->Form->control('concepto');
                echo $this->Form->control('referencia');
                echo $this->Form->control('fecha');
                echo $this->Form->control('confirmacion',['type' => 'select',
                    'options' => ['SI'=>'SI', 'NO'=>'NO'],
                    'empty' => 'Selecciona']);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Guardar')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
    <script>
        $( document ).ready(function() {
            // Handler for .ready() called.
            //  alert('hola mundo');
            console.log('Saludos desde jQuery');

            $('#gasto').blur(function(){
                var gasto = $(this).val();
                console.log('Valor seleccionado ' + gasto);
                var request = $.ajax({
                    url: "/gasto/montoletras",
                    method: "get",
                    data: { 'gasto' :  gasto},
                    dataType: "json"
                });

                request.done(function( msg ) {
                    //var object = JSON.parse(msg);
                    //alert("Respuesta: " + msg.cuota );
                    //$('#abono').val(msg.cuota);
                    $('#monto-letra').val(msg.gasto);
                });

                request.fail(function( jqXHR, textStatus ) {
                    alert( "Request failed: " + textStatus );
                });
            });
        });


    </script>
