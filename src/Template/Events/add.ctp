<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Pago $pago
 */
echo $this->Html->script('jquery.min');
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Pago'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="pago form large-9 medium-8 columns content">
    <?= $this->Form->create($pago) ?>
    <fieldset>
        <legend><?= __('Add Pago') ?></legend>
        <?php
        echo $this->Form->input('id_condominio',['type' => 'select',
            'options' => $optionsCondominio,
            'empty' => 'selecciona']);
        echo $this->Form->input('id_condomino',['type' => 'select']);
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


<script>
    $( document ).ready(function() {
        // Handler for .ready() called.
        //  alert('hola mundo');
        console.log('Saludos desde jQuery');



        $('#id-condominio').on('change', function(){
            var idCond = $(this).val();
            console.log('Valor seleccionado ' + idCond);

            resetErrors();

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
                var options = '<option value="">Selecciona un condómino </option>';
                var selectCondomino = $('#id-condomino');
                selectCondomino.find('option').remove();

                $.each(msg, function (key, val) {
                    //$('<option>').val(val.mun_id).text(val.mun_nombre).appendTo(select);
                    options = options + '<option value=' + val.id_condomino + '>' + val.numero_departamento + '</option>';
                });
                selectCondomino.html(options);
                $('#id-condomino').attr('required', 'required');
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

        function resetErrors() {
            $('form input, form select').removeClass('inputTxtError');
            $('label.error').remove();
        }

    });
</script>


