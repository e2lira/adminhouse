<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Pago $pago
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
                    <li><?= $this->Html->link(__('Lista de Ingresos'), ['controller' => 'Pago', 'action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                    <br>
                    <li style="background-color: #c4e3f3; color: white;"><?= $this->Html->link(__('Cargar Excel'), ['controller' => 'Pago', 'action' => 'excel']) ?></li>
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
                    <!-- <li><span>No. Cuenta:</span> <?= $condominio->no_cuenta_condominio ?></li>
                    <li><span>Banco:</span> <?= $condominio->banco_cuenta ?></li> -->
                </ul>
            </div>
        </div>
        <br><br>
    </div>
    <br>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <div class="page-header">
            <?= $this->Form->create($pago) ?>
            <fieldset>
                <legend><?= __('Nuevo Pago') ?></legend>
                <?php
                echo $this->Form->input('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);

                echo "<div class='row'>";
                    echo "<div class='col-md-6'>";
                        echo $this->Form->control('id_condomino',['type' => 'select',
                            'options' => $optionsCondomino,
                            'empty' => 'selecciona',
                            'label'=> "Condomino"]);
                    echo "</div>";
                    echo "<div class='col-md-6'>";
                        echo $this->Form->input('id_tipo',['type' => 'select',
                            'options' => $optionsTipo,
                            'empty' => 'selecciona',
                            'label'=> "Tipo de pago"]);
                    echo "</div>";
                echo "</div>";
                echo "<div class='row'>";
                echo "<div class='col-md-6'>";
                echo $this->Form->control('abono');
                echo "</div>";
                echo "<div class='col-md-6'>";
                echo $this->Form->control('bonificacion');
                echo "</div>";
                echo "</div>";

                echo "<div class='row'>";
                    echo "<div class='col-md-6'>";
                    echo $this->Form->control('monto_letra');
                echo "</div>";
                    echo "<div class='col-md-6'>";
                echo $this->Form->control('concepto');
                echo "</div>";
                echo "</div>";
                echo $this->Form->control('fecha');

                echo "<div class='row'>";
                    echo "<div class='col-md-4'>";
                    echo $this->Form->control('referencia');
                echo "</div>";
                    echo "<div class='col-md-4'>";
                    echo $this->Form->control('nombre_banco',['type' => 'select',
                    'options' => $optionsBanco,
                    'empty' => 'selecciona']);
                    echo "</div>";
                    echo "<div class='col-md-4'>";
                    echo $this->Form->control('no_cuenta',['type' => 'select',
                        'options' => $optionsCuenta,
                        'empty' => 'selecciona']);
                    echo "</div>";
                echo "</div>";






                ?>
            </fieldset>
            <?= $this->Form->button(__('Guardar'), ['id' => 'guardar-button']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<script>
    $( document ).ready(function() {
        console.log('Saludos desde jQuery');
        $('#abono').blur(function(){
            var monto = $(this).val();
            var request = $.ajax({
                url: "/pago/montoletras",
                method: "get",
                data: { 'monto' :  monto},
                dataType: "json"
            });

            request.done(function( msg ) {
                console.log("Respuesta: " + msg.monto );
                $('#monto-letra').val(msg.monto);
            });

            request.fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });
        });

        $('#id-condomino').select2({
            cache: true,
            serverSide: true,
            placeholder:'Selecciona un condomino',
            dropdownAutoWidth: true,
            theme: "bootstrap4",
            ajax: {
                url:  '/pago/getCondominos/' + <?= $condominio->id_condominio ?>,
                dataType: 'json',
                data: function(params) {
                    var query={
                        search: params.term,
                        page: params.page || 1,
                        size: '5'
                    }
                    return query;
                }
            }
        });

    });
</script>

<script>
    $(document).ready(function() {
        $('#guardar-button').on('click', function(e) {
            e.preventDefault();

            window.open('<?= $this->Url->build(['action' => 'add', 'id' => $condominio->id_condominio]) ?>', '_blank');

            $(this).closest('form').submit();
        });
    });
</script>
