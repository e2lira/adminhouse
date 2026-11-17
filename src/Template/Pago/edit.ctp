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
                <ul class="side-nav">
                    <li class="heading"><?= __('ActiGons') ?></li>
                    <li><?= $this->Form->postLink(
                        __('Delete'),
                        ['action' => 'delete', $pago->id_pago],
                        ['confirm' => __('Are you sure you want to delete # {0}?', $pago->id_pago)]
                        )
                        ?></li>
                    <li><?= $this->Html->link(__('Lista de Pago'), ['action' => 'index', 'id'=>$id_condominio]) ?></li>
                </ul>
            </div>
        </div>
        <br><br>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($pago) ?>
    <fieldset>
        <legend><?= __('Edit Pago') ?></legend>
        <?php
            echo $this->Form->input('id_condominio',['type' => 'select',
        'options' => $optionsCondominio,
        'empty' => 'selecciona']);

        echo $this->Form->control('id_condomino',['type' => 'select',
        'options' => $optionsCondomino,
        'empty' => 'selecciona']);

        echo $this->Form->input('id_tipo',['type' => 'select',
        'options' => $optionsTipo,
        'empty' => 'selecciona']);

        echo $this->Form->control('abono');
        echo $this->Form->control('monto_letra');
        echo $this->Form->control('concepto');
        // echo $this->Form->control('fecha_pago', ['empty' => true]);
        echo $this->Form->control('fecha');
        echo $this->Form->control('referencia');
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



        $('#id-condominio').on('change', function(){
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
            });

            requestDepartamentos.fail(function( jqXHR, textStatus ) {
                alert( "Request failed: " + textStatus );
            });

        });

        $('#abono').blur(function(){
            var monto = $(this).val();
            console.log('Valor seleccionado ' + monto);
            var request = $.ajax({
                url: "/pago/montoletras",
                method: "get",
                data: { 'monto' :  monto},
                dataType: "json"
            });

            request.done(function( msg ) {
                //var object = JSON.parse(msg);
                //alert("Respuesta: " + msg.cuota );
                //$('#abono').val(msg.cuota);
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
</script>
