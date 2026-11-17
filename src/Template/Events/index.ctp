<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<?php

    echo $this->Html->css('fullcalendar.min');
    //echo $this->Html->css('bootstrap');

    echo $this->Html->script('moment');
    //echo $this->Html->script('jquery.min');
    echo $this->Html->script('fullcalendar.min');
    echo $this->Html->script('jquery.validate.min');
?>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<script src='js/locale/es.js'></script>

<style>
    .removebtn {
        color:black;
        position: absolute;
        top: 0;
        right: 0;
        width:13px;
        height: 13px;
        text-align:center;
        border-radius:50%;
        font-size: 10px;
        cursor: pointer;
        background-color: #FFF
    }
</style>
<br>
<div class="container">


    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="calendar-tab" data-toggle="tab" href="#calendar" role="tab" aria-controls="home" aria-selected="true">Crear y Editar</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="caldel-tab" data-toggle="tab" href="#caldel" role="tab" aria-controls="profile" aria-selected="false">Borrar</a>
        </li>

    </ul>


   <div class="tab-content">
       <div class="tab-pane fade show active" id="calendar" role="tabpanel" aria-labelledby="home-tab"></div>
       <div class="tab-pane fade" id="caldel" role="tabpanel" aria-labelledby="profile-tab"></div>
   </div>

</div>
<div id="fullCalModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span> <span class="sr-only">close</span></button>
                <h4 id="modalTitle" class="modal-title">Eventos</h4>
            </div>
            <div id="modalBody" class="modal-body">
                <intput type="text" name="texto"></intput>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <!--<button class="btn btn-primary"><a id="eventUrl" target="_blank">Event Page</a></button> -->
            </div>
        </div>
    </div>
</div>

<div id="fullCalModalForm" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span> <span class="sr-only">close</span></button>
            </div>
            <div id="modalBody" class="modal-body">
                <?php
                echo $this->Form->create(null, [
                'id' => 'form-event',
                'url' => ['controller' => 'Events', 'action' => 'add']
                ]);
                ?>
                <fieldset>
                    <legend><?= __('Add Evento') ?></legend>
<!--                    <input type="hidden" id="_Token" name="_Token" value="" />-->
                <?php


                    echo $this->Form->input('start', ['type' => 'text']);
                    echo $this->Form->input('end', ['type' => 'text']);

                    echo $this->Form->input('id_condominio',['type' => 'select',
                                        'options' => $optionsCondominio,
                                        'required' => 'required',
                                        'empty' => 'Selecciona']);

                    echo $this->Form->control('id_condomino',['type' => 'select',
                                            'required' => 'required']);

                    echo $this->Form->control('id_amenidad', ['type' => 'select',
                                        'required' => 'required',
                                        'empty' => 'Selecciona una amenidad']);
                    echo $this->Form->input('responsable', ['required' => 'true',
                                                    'type' => 'text']);
                    echo $this->Form->input('title', [ 'required' => 'true',
                                                    'type' => 'text']);
                  ?>
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="button" id="save" class="btn btn-primary">Guardar</button>
                <button type="button" id="update" class="btn btn-primary">Editar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Dialog -->
<div class="modal fade" id="confirmDelete" role="dialog" aria-labelledby="confirmDeleteLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                        aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Parmanently</h4>
            </div>
            <div class="modal-body"   style="height: 75px">
                <p>Hola</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger"  data-id="" data-X-CSRF-Token="<?= $_csrfToken ?>" id="confirm-delete-ok">Ok
                </button>
            </div>
        </div>
    </div>
<script>
    $(document).ready(function() {

        var csrfToken = <?= json_encode($this->request->getParam('_csrfToken')) ?>;
        console.log(csrfToken);
        var start;
        var end;
        var id_edit;



        var calendar = $('#calendar').fullCalendar({
                locale: 'es',
                defaultView: 'agendaWeek',
                editable: true,
                overlap: false,
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'agendaWeek,agendaDay,listWeek',
                    views: {
                        listDay: {buttonText: 'list Day'},
                        listWeek: {buttonText: 'list Week'}
                    },
                }, events: {
                    url: '/events/load',
                    type: 'GET',
                    dataType: 'json',
                    error: function() {
                        alert('there was an error while fetching events!');
                    },
                    color: 'orange',   // a non-ajax option
                    textColor: 'black' // a non-ajax option
            },
            eventClick:  function(event, jsEvent, view) {
                //$('#modalTitle').html(event.title);
                var list = "<ul><li>Título: " + event.title + "</li><li>Responsable: " + event.responsable +"</li><li>Condominio: " + event.condominio +"</li><li>Condómino: " + event.condomino +"</li><li>Amenidad: " + event.amenidad +"</li></ul>"
                $('#modalBody').html(list);
                $('#eventUrl').attr('href', event.url);
                $('#modalTitle').css("color", event.color);
                $('#modalTitle').css("text-align", 'left');
                $('#fullCalModal').on('show.bs.modal', function (e) {
                });
                $('#fullCalModal').modal('show');
            },
           // },
            // dayClick: function(date, jsEvent, view) {
            //     alert('Clicked on: ' + date.format());
            //     alert('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
            //     alert('Current view: ' + view.name);
            //     // change the day's background color just for fun
            //     $(this).css('background-color', 'red');
            // },
            selectable:true,
            selectHelper:true,
            select: function(start, end, allDay)
            {
               // var title = prompt("Enter Event Title");
                start = $.fullCalendar.formatDate(start, "Y-MM-DD HH:mm:ss");
                end = $.fullCalendar.formatDate(end, "Y-MM-DD HH:mm:ss");
                console.log(start);
                $('#fullCalModalForm').on('show.bs.modal', function (e) {
                    console.log('Ingresando a la acción de abrir modal ' + start);
                    //populate the textbox
                    $(e.currentTarget).find('input[name="start"]').val(start);
                    $(e.currentTarget).find('input[name="end"]').val(end);
                    $(e.currentTarget).find('input[name="responsable"]').val('');
                    $(e.currentTarget).find('input[name="title"]').val('');
                    clearSelects();
                    $(e.currentTarget).find('button[id="update"]').hide();
                    $(e.currentTarget).find('button[id="save"]').show();
                })
                $('#fullCalModalForm').modal('show');
            },
            eventDrop: function( event, dayDelta, revertFunc, jsEvent, ui, view ){

                if (!confirm("Are you sure about this change?")) {
                    revertFunc();
                }

                $('#fullCalModalForm').modal('show');
                $('#fullCalModalForm').on('shown.bs.modal', function (e) {
                    console.log('Ingresando a la acción de abrir modal en formato Update' + event.start.format());
                    //populate the textbox
                    id_edit = event.id;
                    $(e.currentTarget).find('button[id="update"]').show();
                    $(e.currentTarget).find('button[id="save"]').hide();
                    $.ajax({
                        async: true,
                        cache: false,
                        url: '/events/findById',
                        type: 'GET',
                        data: { 'id_evento': event.id },
                        dataType: 'json',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-CSRF-Token', "<?= $this->request->getParams['_csrfToken'] ?>");
                        },
                        success:function(msg)
                        {
                            var evento = msg[0];
                            console.log(" Titulo del evento: " + evento.title);
                            $(e.currentTarget).find('select[name="id_tipo"]').val(evento.id_tipo);
                            $(e.currentTarget).find('select[name="id_condominio"]').val(evento.id_condominio);
                            fillSelectCondomino(evento.id_condominio, $('#id-condomino'), evento.id_condomino);
                            //$(e.currentTarget).find('select[name="id_condomino"]').val(evento.id_condomino);
                            $(e.currentTarget).find('input[name="responsable"]').val(evento.responsable);
                        },
                        error:function(jqXHR, textStatus, errorThrown){
                            alert(jqXHR);
                        }
                    });
                    $(e.currentTarget).find('input[name="title"]').val(event.title);
                    $(e.currentTarget).find('input[name="start"]').val(event.start.format('YYYY-MM-DD hh:mm:ss'));
                    $(e.currentTarget).find('input[name="end"]').val(event.end.format('YYYY-MM-DD hh:mm:ss'));

                })
                //updateForm(event);
            },
            eventOverlap: function(stillEvent, movingEvent) { // Para que no se traslapen los eventos
                return stillEvent.allDay && movingEvent.allDay;
            }

        });


            var calendar_del = $('#caldel').fullCalendar({
                defaultView: 'agendaWeek',
                editable: true,
                overlap: false,
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'agendaWeek,agendaDay,listWeek',
                    views: {
                        listDay: {buttonText: 'list Day'},
                        listWeek: {buttonText: 'list Week'}
                    },
                }, events: {
                    url: '/events/load',
                    type: 'GET',
                    dataType: 'json',
                    error: function () {
                        alert('there was an error while fetching events!');
                    },
                    color: 'orange',   // a non-ajax option
                    textColor: 'black' // a non-ajax option
                },
                eventRender: function (event, element) {
                    element.append("<span class='removebtn'>X</span>");
                    element.find(".removebtn").click(function () {
                        console.log('click borrar id: ' + event.id);

                    });
                },
                eventClick: function (event, jsEvent, view) {
                    $('#confirmDelete').modal('show');
                    $('#confirmDelete').on('shown.bs.modal', function (e) {
                        var message = $(e.relatedTarget).attr('data-message');
                        console.log('Evento id: ' + event.id);
                        $(e.currentTarget).find('.modal-body p').text("¿Desea eleminiar el evento: '" + event.title + "' ?");
                        $(e.currentTarget).find('button[id="confirm-delete-ok"]').attr('data-id', event.id);

                    });
                },
            });


        $('#confirm-delete-ok').on('click', function () {
            var id  = $(this).attr('data-id');
            console.log('Se borrara el id: ' + id)
            $.ajax({
                method: "get",
                url: '/events/delete/' + id,
                dataType: 'json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-CSRF-Token', $(this).attr("x-csrf-token"));
                },
              success: function(msg) {
                    result = msg[0];
                    console.log(result.message);
                    if (result.result === 'ok') {
                        calendar_del.fullCalendar('removeEvents', id);
                        calendar.fullCalendar('refetchEvents');
                    }
                },
                error:function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                }
            });
            $('#confirmDelete').modal('hide');
            return false;
        });

        function clearSelects(){
            $('#id-condominio').prop("selectedIndex", 0);
            $('#id-condomino').val(null).trigger('change.select2');
            $('#id-amenidad').val(null).trigger('change.select2');
        }

        $('#id-condomino').select2({
            dropdownParent: $('#fullCalModalForm'),
            width: 'resolve',
            allowClear: true,
            cache: true,
            serverSide: true,
            placeholder:'Selecciona condómino',
            dropdownAutoWidth: true,
            theme: "bootstrap4",
            ajax: {
                url:  '/pago/getCondominos',
                dataType: 'json',
                data: function(params) {
                    var query={
                        id_condominio: id,
                        search: params.term,
                        page: params.page || 1,
                        size: '5'
                    }
                    return query;
                }
            }
        });

        $('#id-condominio').on('change', function(){
            var idCond = $(this).val();
            console.log('Valor seleccionado ' + idCond);
            fillSelect('/pago/getCondominos/' + idCond, $('#id-condomino'), 0);
            fillSelect('/amenidad/getAmenidadByCondominio/' + idCond, $('#id-amenidad'), 0);

        });

        function fillSelect(url, select, idSelected){

            select.select2({
                cache: true,
                serverSide: true,
                placeholder:'Selecciona una amenidad',
                dropdownAutoWidth: true,
                theme: "bootstrap4",
                ajax: {
                    url:  url,
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

        }

         $('#save').on('click',function(event){

             valid = $('#form-event').validate().form();
             if (valid) {
                 submitForm();
             }
             event.preventDefault();
             return false;
        });


        function submitForm(){
            var $form = $('#form-event');
            var $target = $($form.attr('data-target'));
            var submitButton = $("input[type='submit'][clicked=true], button[type='submit'][clicked=true]", $form);

            //var data = $( "form" ).serialize();
            var formData = $form.serializeArray();

            console.log("Ingresando el metodo submit!");

            $.ajax({
                async: true,
                cache: false,
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: formData,
                dataType: 'json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-CSRF-Token', "<?= $this->request->getParams['_csrfToken'] ?>");
                },
                success:function(msg)
                {
                    calendar.fullCalendar('refetchEvents');
                    calendar_del.fullCalendar('refetchEvents');
                    //alert(" " + msg.message);
                    //cleanInputs();
                    console.log(msg.message);
                    $('#fullCalModalForm').modal('hide');

                    $('#modalBody').html(msg.message);
                    $('#fullCalModal').modal('show');
                },
                error:function(jqXHR, textStatus, errorThrown){
                    alert(jqXHR);
                }
            });
        }

        $('#update').on('click',function(event){

            valid = $('#form-event').validate().form();
            if (valid) {
                updateForm();
            }
            event.preventDefault();
            return false;
        });

        function updateForm(){
            var $form = $('#form-event');

            //var data = $( "form" ).serialize();
            var formData = $form.serializeArray();

            console.log("Ingresando el metodo submit!");
            //var data = $( "form" ).serialize();
            //var formData = event.serializeArray();

            //console.log("Ingresando el metodo updateForm! " + formData);

            $.ajax({
                async: true,
                cache: false,
                url: '/events/edit/' + id_edit,
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-CSRF-Token', "<?= $this->request->getParams['_csrfToken'] ?>");
                },
                success:function(msg)
                {
                    calendar.fullCalendar('refetchEvents');
                    calendar_del.fullCalendar('refetchEvents');
                    //alert(" " + msg.message);
                    //cleanInputs();
                    console.log(msg.message);
                    $('#fullCalModalForm').modal('hide');

                    $('#modalBody').html(msg.message);
                    $('#fullCalModal').modal('show');
                },
                error:function(jqXHR, textStatus, errorThrown){
                    alert(jqXHR);
                }
            });
        }



        $(document).on("click", 'input[type="submit"], button[type="submit"]', function() {
            $('form input[type=submit], form button[type=submit]', $(this).parents("form")).removeAttr("clicked");
            $(this).attr("clicked", "true");
        });

        function cleanInputs(){
            $('#title').val('');
            $('#responsable').val('');
            $('#id-condominio').val('Selecciona');
            $("#id-condominio").each(function(){ $(this).find('option[value="'+$(this).attr("value")+'"]').prop('selected', true); });
        }



    });

</script>
