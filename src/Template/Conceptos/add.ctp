<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Concepto $concepto
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
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
                    <li><?= $this->Html->link(__('Lista de Gasto'), ['controller' => 'Gasto', 'action' => 'index', 'id'=>$gasto['id_condominio']]) ?></li>
                    <br>
                    <li style="background-color: #c4e3f3; color: white;"><?= $this->Html->link(__('Caja chica'), ['controller' => 'caja', 'action' => 'add', 'id'=>$gasto['id_condominio']]) ?></li>

                </ul>
            </div>
        </div>

        <br><br>
    </div>

    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($concepto) ?>
    <fieldset>
        <legend><?= __('Agregar Concepto') ?></legend>
        <?php
            echo $this->Form->control('id_gasto',['type' => 'hidden', 'value'=>$gasto->id_gasto]);
            echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$gasto->id_condominio]);
            echo $this->Form->control('concept');
            echo $this->Form->control('gasto');
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

            /*$('#id-condominio').on('change', function(){
            var idCond = $(this).val();
            console.log('Valor seleccionado ' + idCond);
            var request = $.ajax({
              url: "/pago/condominio",
              method: "get",
              data: { id_condominio :  idCond},
              dataType: "json"
            });

            request.done(function( msg ) {
              //var object = JSON.parse(msg);
              //alert("Respuesta: " + msg.cuota );
              $('#abono').val(msg.cuota);
              $('#monto-letra').val(msg.cuota_letra);
            });

            request.fail(function( jqXHR, textStatus ) {
              alert( "Request failed: " + textStatus );
            });
            /!*
                var requestDepartamentos = $.ajax({
                  url: "/pago/getDepartamentos",
                  method: "get",
                  data: { id_condominio :  idCond},
                  dataType: "json"
                });

               requestDepartamentos.done(function( msg ) {
                  //var object = JSON.parse(msg);
                  //alert("Respuesta: " + msg.cuota );
                  var options = '<option value="0">Selecciona un condómino </option>';
                  var selectCondomino = $('#id-condomino');
                        selectCondomino.find('option').remove();

                        $.each(msg, function (key, val) {
                            //$('<option>').val(val.mun_id).text(val.mun_nombre).appendTo(select);
                            options = options + '<option value=' + val.id_condomino + '>' + val.numero_departamento + '</option>';
                        });
                        selectCondomino.html(options);
                });*!/

            requestDepartamentos.fail(function( jqXHR, textStatus ) {
              alert( "Request failed: " + textStatus );
            });

            });*/

            $('#gasto').blur(function(){
                var gasto = $(this).val();
                //console.log('Valor seleccionado ' + monto);
                var request = $.ajax({
                    url: "/conceptos/montoletras",
                    method: "get",
                    data: { 'gasto' :  gasto},
                    dataType: "json"
                });

                request.done(function( msg ) {
                    //var object = JSON.parse(msg);
                    console.log("Respuesta: " + msg.gasto );
                    $('#monto-letra').val(msg.monto);
                });

                request.fail(function( jqXHR, textStatus ) {
                    alert( "Request failed: " + textStatus );
                });
            });

            $('#id-condomino').select2({
                ache: true,
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
    </script>s
