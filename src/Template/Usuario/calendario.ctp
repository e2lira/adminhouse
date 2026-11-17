
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<!------ Include the above in your HEAD tag ---------->
<?= $this->Html->css('bootstrap') ?>
<?= $this->Html->css('select2') ?>
<?= $this->Html->css('select2-bootstrap4')?>
<?= $this->Html->css('minicolors')?>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

<?= $this->Html->script('jquery.min') ?>
<?= $this->Html->script('select2.full.min')?>
<?= $this->Html->script('bootstrap') ?>
<?= $this->Html->script('minicolors') ?>
<?= $this->Html->script('minicolors') ?>
<?php

echo $this->Html->css('fullcalendar.min');
//echo $this->Html->css('bootstrap');

echo $this->Html->script('moment');
//echo $this->Html->script('jquery.min');
echo $this->Html->script('fullcalendar.min');
echo $this->Html->script('jquery.validate.min');
?>
<?php

echo $this->Html->css('fullcalendar.min');
//echo $this->Html->css('bootstrap');

echo $this->Html->script('moment');
//echo $this->Html->script('jquery.min');
echo $this->Html->script('fullcalendar.min');
echo $this->Html->script('jquery.validate.min');
echo $this->Html->script('locale/es');
?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
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
<div class="container">
    <div class="view-account">
        <section class="module">
            <div class="module-inner">
                <div class="side-bar">
                    <div class="user-info">
                        <img src="<?php echo '/../files/Condominio/logo/'.$inf['logo'] ?>" alt="<?php echo '/', $inf['logo'] ?>" style="width:100px;">
                        <ul class="meta list list-unstyled">
                            <li class="name"><b><?= h($inf['nombre_condominio']) ?></b>
                                <br>
                            </li>
                            <li class="email"><a href="tel:<?= h($inf['telefono_condominio']) ?>"><?= h($inf['telefono_condominio']) ?></a></li>
                            <li class="activity"><?= h($inf['direccion_condominio']) ?></li>
                            <li class="activity"><?= h($user['marca']) ?></li>
                            <li class="activity"><?= h($user['modelo']) ?></li>
                            <li class="activity"><?= h($user['color']) ?></li>
                            <li class="activity"><?= h($user['placas']) ?></li>

                        </ul>
                    </div>
                    <nav class="side-menu">
                        <ul class="nav">
                            <li><a href="/Usuario/profile"><span class="fa fa-user"></span> Perfil</a></li>
                            <li><a href="/Usuario/configuracion"><span class="fa fa-cog"></span> Configuración</a></li>
                            <li><a href="/Usuario/calendario"><span class="fa fa-calendar"></span> Reservaciones</a></li>
                            <li><a href="/Usuario/mensajes"><span class="fa fa-envelope"></span> Mensajes <?= h($Query->count) ?></a></li>
                            <li><a href="/Usuario/pagos"><span class="fa fa-file"></span> Pagos</a></li>
                            <li><a href="/Usuario/comunicado"><span class="fa fa-clock-o"></span> Comunicados</a></li>
                            <li><a href="/Usuario/reglamento"><span class="fa fa-align-justify"></span> Reglamento</a></li>
                            <li><a href="/Users/logout"><span class="fa fa-reply"></span> Salir</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="content-panel">
                    <div class="content-header-wrapper">
                        <h2 class="title"><b style="color: lightslategrey"><?= h($user['residente']) ?></h2>
                        <div class="actions"  style="">
                           <!-- <button id="myBtn" class="btn btn-info">Reservar</button> -->


                        </div>
                    <br>
                    <br>
                    </div>

                    <div class="content-utilities" >

                  <?php
                            if($user['estatus']=='ACTIVO'){
                               echo '   <div class="drive-wrapper drive-grid-view">
                        <h3><i class="fa fa-calendar"></i> Reservaciones</h3>
                        <p>Una vez hecha la reservación contara con 3 días para realizar su pago.</p>
                    </div> ';
                      }else{
                                echo '';
                            }

              ?>
                    <br>
                    <div class="drive-wrapper drive-grid-view">

                        <div class="container">

                            <?php
                            if($user['estatus']=='ACTIVO'){
                               echo '
                           <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="calendar-tab" data-toggle="tab" href="#calendar" role="tab" aria-controls="home" aria-selected="true">Agendar</a>
                                </li>


                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="calendar" role="tabpanel" aria-labelledby="home-tab"></div>
                                <div class="tab-pane fade" id="caldel" role="tabpanel" aria-labelledby="profile-tab"></div>
                            </div>
                               ';
                            }else{
                                echo '
                                    <center>
                                    <label style="font-size: 20px; color: #e4606d;">¡ALERTA!</label>
                                    <br>
                                    <label>Usted tiene adeudos, favor de comunicarse con su administrador.</label>
                                    </center>
                                     ';
                            }
                            ?>



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
                                            <legend><?= __('Agendar Evento') ?></legend>
                                            <!--                    <input type="hidden" id="_Token" name="_Token" value="" />-->
                                            <?php

                                            echo $this->Form->input('start', ['type' => 'text']);
                                            echo $this->Form->input('end', ['type' => 'text']);

                                            echo $this->Form->input('id_condominio',['type'=>'hidden','value'=> $inf['id_condominio']]);

                                            echo $this->Form->control('id_condomino',['type'=>'hidden','value'=>$user['id_condomino']]);

                                            echo $this->Form->input('id_amenidad',['type' => 'select',
                                                'options' => $amenidades,
                                                'empty' => 'selecciona']);

                                            echo $this->Form->input('responsable', ['required' => 'true',
                                                'type' => 'text']);
                                            echo $this->Form->input('title');
                                            echo 'Numero de usuarios';
                                            echo $this->Form->select(
                                                'no_usuarios',
                                                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                                                ['empty' => 'Selecciona']
                                            );                                            ?>
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

                    </div>
        </section>

    </div>

</div>
<div id="myModal" class="modal">
    <script>
        $( function() {
            $( "#start" ).datepicker();
        } );
        $( function() {
            $( "#end" ).datepicker();
        } );
    </script>
    <!-- Modal content -->
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="pago form large-9 medium-8 columns content">
            <?php


            echo $this->Form->input('start');
            echo $this->Form->input('end');

            echo $this->Form->input('id_condominio');

            echo $this->Form->control('id_condomino',['value'=>$user['id_condomino']]);

            echo $this->Form->control('id_amenidad');
            echo $this->Form->input('responsable', ['required' => 'true',
                'type' => 'text']);
            echo $this->Form->input('title', [ 'required' => 'true',
                'type' => 'text']);
            ?>
            </fieldset>
            <?= $this->Form->button(__('Guardar')) ?>
            <?= $this->Form->end() ?>
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
               //$('#id-condomino').val(null).trigger('change.select2');
               $('#id-amenidad').val(null).trigger('change.select2');
           }
        /*
                 $('#id-condominow').select2({
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

                  $('#id-condominiow').on('change', function(){
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

                  }  */

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
<style>
    .modal-backdrop {
        z-index: 100000 !important;
    }

    .modal {
        z-index: 100001 !important;
    }
    body {font-family: Arial, Helvetica, sans-serif;}

    /* The Modal (background) */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        padding-top: 100px; /* Location of the box */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    /* Modal Content */
    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }

    /* The Close Button */
    .close {
        color: #aaaaaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: #000;
        text-decoration: none;
        cursor: pointer;
    }
</style>
<script>
    // Get the modal
    var modal = document.getElementById("myModal");

    // Get the button that opens the modal
    var btn = document.getElementById("myBtn");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks the button, open the modal
    btn.onclick = function() {
        modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
<style>

    body{
        background:#f9f9fb;
    }
    .view-account{
        background:#FFFFFF;
        margin-top:20px;
    }
    .view-account .pro-label {
        font-size: 13px;
        padding: 4px 5px;
        position: relative;
        top: -5px;
        margin-left: 10px;
        display: inline-block
    }

    .view-account .side-bar {
        padding-bottom: 30px
    }

    .view-account .side-bar .user-info {
        text-align: center;
        margin-bottom: 15px;
        padding: 30px;
        color: #616670;
        border-bottom: 1px solid #f3f3f3
    }

    .view-account .side-bar .user-info .img-profile {
        width: 120px;
        height: 120px;
        margin-bottom: 15px
    }

    .view-account .side-bar .user-info .meta li {
        margin-bottom: 10px
    }

    .view-account .side-bar .user-info .meta li span {
        display: inline-block;
        width: 100px;
        margin-right: 5px;
        text-align: right
    }

    .view-account .side-bar .user-info .meta li a {
        color: #616670
    }

    .view-account .side-bar .user-info .meta li.activity {
        color: #a2a6af
    }

    .view-account .side-bar .side-menu {
        text-align: center
    }

    .view-account .side-bar .side-menu .nav {
        display: inline-block;
        margin: 0 auto
    }

    .view-account .side-bar .side-menu .nav>li {
        font-size: 14px;
        margin-bottom: 0;
        border-bottom: none;
        display: inline-block;
        float: left;
        margin-right: 15px;
        margin-bottom: 15px
    }

    .view-account .side-bar .side-menu .nav>li:last-child {
        margin-right: 0
    }

    .view-account .side-bar .side-menu .nav>li>a {
        display: inline-block;
        color: #9499a3;
        padding: 5px;
        border-bottom: 2px solid transparent
    }

    .view-account .side-bar .side-menu .nav>li>a:hover {
        color: #616670;
        background: none
    }

    .view-account .side-bar .side-menu .nav>li.active a {
        color: #40babd;
        border-bottom: 2px solid #40babd;
        background: none;
        border-right: none
    }

    .theme-2 .view-account .side-bar .side-menu .nav>li.active a {
        color: #6dbd63;
        border-bottom-color: #6dbd63
    }

    .theme-3 .view-account .side-bar .side-menu .nav>li.active a {
        color: #497cb1;
        border-bottom-color: #497cb1
    }

    .theme-4 .view-account .side-bar .side-menu .nav>li.active a {
        color: #ec6952;
        border-bottom-color: #ec6952
    }

    .view-account .side-bar .side-menu .nav>li .icon {
        display: block;
        font-size: 24px;
        margin-bottom: 5px
    }

    .view-account .content-panel {
        padding: 30px
    }

    .view-account .content-panel .title {
        margin-bottom: 15px;
        margin-top: 0;
        font-size: 18px
    }

    .view-account .content-panel .fieldset-title {
        padding-bottom: 15px;
        border-bottom: 1px solid #eaeaf1;
        margin-bottom: 30px;
        color: #616670;
        font-size: 16px
    }

    .view-account .content-panel .avatar .figure img {
        float: right;
        width: 64px
    }

    .view-account .content-panel .content-header-wrapper {
        position: relative;
        margin-bottom: 30px
    }

    .view-account .content-panel .content-header-wrapper .actions {
        position: absolute;
        right: 0;
        top: 0
    }

    .view-account .content-panel .content-utilities {
        position: relative;
        margin-bottom: 30px
    }

    .view-account .content-panel .content-utilities .btn-group {
        margin-right: 5px;
        margin-bottom: 15px
    }

    .view-account .content-panel .content-utilities .fa {
        font-size: 16px;
        margin-right: 0
    }

    .view-account .content-panel .content-utilities .page-nav {
        position: absolute;
        right: 0;
        top: 0
    }

    .view-account .content-panel .content-utilities .page-nav .btn-group {
        margin-bottom: 0
    }

    .view-account .content-panel .content-utilities .page-nav .indicator {
        color: #a2a6af;
        margin-right: 5px;
        display: inline-block
    }

    .view-account .content-panel .mails-wrapper .mail-item {
        position: relative;
        padding: 10px;
        border-bottom: 1px solid #f3f3f3;
        color: #616670;
        overflow: hidden
    }

    .view-account .content-panel .mails-wrapper .mail-item>div {
        float: left
    }

    .view-account .content-panel .mails-wrapper .mail-item .icheck {
        background-color: #fff
    }

    .view-account .content-panel .mails-wrapper .mail-item:hover {
        background: #f9f9fb
    }

    .view-account .content-panel .mails-wrapper .mail-item:nth-child(even) {
        background: #fcfcfd
    }

    .view-account .content-panel .mails-wrapper .mail-item:nth-child(even):hover {
        background: #f9f9fb
    }

    .view-account .content-panel .mails-wrapper .mail-item a {
        color: #616670
    }

    .view-account .content-panel .mails-wrapper .mail-item a:hover {
        color: #494d55;
        text-decoration: none
    }

    .view-account .content-panel .mails-wrapper .mail-item .checkbox-container,
    .view-account .content-panel .mails-wrapper .mail-item .star-container {
        display: inline-block;
        margin-right: 5px
    }

    .view-account .content-panel .mails-wrapper .mail-item .star-container .fa {
        color: #a2a6af;
        font-size: 16px;
        vertical-align: middle
    }

    .view-account .content-panel .mails-wrapper .mail-item .star-container .fa.fa-star {
        color: #f2b542
    }

    .view-account .content-panel .mails-wrapper .mail-item .star-container .fa:hover {
        color: #868c97
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-to {
        display: inline-block;
        margin-right: 5px;
        min-width: 120px
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject {
        display: inline-block;
        margin-right: 5px
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label {
        margin-right: 5px
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label:last-child {
        margin-right: 10px
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label a,
    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label a:hover {
        color: #fff
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label-color-1 {
        background: #f77b6b
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label-color-2 {
        background: #58bbee
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label-color-3 {
        background: #f8a13f
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label-color-4 {
        background: #ea5395
    }

    .view-account .content-panel .mails-wrapper .mail-item .mail-subject .label-color-5 {
        background: #8a40a7
    }

    .view-account .content-panel .mails-wrapper .mail-item .time-container {
        display: inline-block;
        position: absolute;
        right: 10px;
        top: 10px;
        color: #a2a6af;
        text-align: left
    }

    .view-account .content-panel .mails-wrapper .mail-item .time-container .attachment-container {
        display: inline-block;
        color: #a2a6af;
        margin-right: 5px
    }

    .view-account .content-panel .mails-wrapper .mail-item .time-container .time {
        display: inline-block;
        text-align: right
    }

    .view-account .content-panel .mails-wrapper .mail-item .time-container .time.today {
        font-weight: 700;
        color: #494d55
    }

    .drive-wrapper {
        padding: 15px;
        background: #f5f5f5;
        overflow: hidden
    }

    .drive-wrapper .drive-item {
        width: 130px;
        margin-right: 15px;
        display: inline-block;
        float: left
    }

    .drive-wrapper .drive-item:hover {
        box-shadow: 0 1px 5px rgba(0, 0, 0, .1);
        z-index: 1
    }

    .drive-wrapper .drive-item-inner {
        padding: 15px
    }

    .drive-wrapper .drive-item-title {
        margin-bottom: 15px;
        max-width: 100px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis
    }

    .drive-wrapper .drive-item-title a {
        color: #494d55
    }

    .drive-wrapper .drive-item-title a:hover {
        color: #40babd
    }

    .theme-2 .drive-wrapper .drive-item-title a:hover {
        color: #6dbd63
    }

    .theme-3 .drive-wrapper .drive-item-title a:hover {
        color: #497cb1
    }

    .theme-4 .drive-wrapper .drive-item-title a:hover {
        color: #ec6952
    }

    .drive-wrapper .drive-item-thumb {
        width: 100px;
        height: 80px;
        margin: 0 auto;
        color: #616670
    }

    .drive-wrapper .drive-item-thumb a {
        -webkit-opacity: .8;
        -moz-opacity: .8;
        opacity: .8
    }

    .drive-wrapper .drive-item-thumb a:hover {
        -webkit-opacity: 1;
        -moz-opacity: 1;
        opacity: 1
    }

    .drive-wrapper .drive-item-thumb .fa {
        display: inline-block;
        font-size: 36px;
        margin: 0 auto;
        margin-top: 20px
    }

    .drive-wrapper .drive-item-footer .utilities {
        margin-bottom: 0
    }

    .drive-wrapper .drive-item-footer .utilities li:last-child {
        padding-right: 0
    }

    .drive-list-view .name {
        width: 60%
    }

    .drive-list-view .name.truncate {
        max-width: 100px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis
    }

    .drive-list-view .type {
        width: 15px
    }

    .drive-list-view .date,
    .drive-list-view .size {
        max-width: 60px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis
    }

    .drive-list-view a {
        color: #494d55
    }

    .drive-list-view a:hover {
        color: #40babd
    }

    .theme-2 .drive-list-view a:hover {
        color: #6dbd63
    }

    .theme-3 .drive-list-view a:hover {
        color: #497cb1
    }

    .theme-4 .drive-list-view a:hover {
        color: #ec6952
    }

    .drive-list-view td.date,
    .drive-list-view td.size {
        color: #a2a6af
    }

    @media (max-width:767px) {
        .view-account .content-panel .title {
            text-align: center
        }
        .view-account .side-bar .user-info {
            padding: 0
        }
        .view-account .side-bar .user-info .img-profile {
            width: 60px;
            height: 60px
        }
        .view-account .side-bar .user-info .meta li {
            margin-bottom: 5px
        }
        .view-account .content-panel .content-header-wrapper .actions {
            position: static;
            margin-bottom: 30px
        }
        .view-account .content-panel {
            padding: 0
        }
        .view-account .content-panel .content-utilities .page-nav {
            position: static;
            margin-bottom: 15px
        }
        .drive-wrapper .drive-item {
            width: 100px;
            margin-right: 5px;
            float: none
        }
        .drive-wrapper .drive-item-thumb {
            width: auto;
            height: 54px
        }
        .drive-wrapper .drive-item-thumb .fa {
            font-size: 24px;
            padding-top: 0
        }
        .view-account .content-panel .avatar .figure img {
            float: none;
            margin-bottom: 15px
        }
        .view-account .file-uploader {
            margin-bottom: 15px
        }
        .view-account .mail-subject {
            max-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }
        .view-account .content-panel .mails-wrapper .mail-item .time-container {
            position: static
        }
        .view-account .content-panel .mails-wrapper .mail-item .time-container .time {
            width: auto;
            text-align: left
        }
    }

    @media (min-width:768px) {
        .view-account .side-bar .user-info {
            padding: 0;
            padding-bottom: 15px
        }
        .view-account .mail-subject .subject {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }
    }

    @media (min-width:992px) {
        .view-account .content-panel {
            min-height: 800px;
            border-left: 1px solid #f3f3f7;
            margin-left: 200px
        }
        .view-account .mail-subject .subject {
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }
        .view-account .side-bar {
            position: absolute;
            width: 200px;
            min-height: 600px
        }
        .view-account .side-bar .user-info {
            margin-bottom: 0;
            border-bottom: none;
            padding: 30px
        }
        .view-account .side-bar .user-info .img-profile {
            width: 120px;
            height: 120px
        }
        .view-account .side-bar .side-menu {
            text-align: left
        }
        .view-account .side-bar .side-menu .nav {
            display: block
        }
        .view-account .side-bar .side-menu .nav>li {
            display: block;
            float: none;
            font-size: 14px;
            border-bottom: 1px solid #f3f3f7;
            margin-right: 0;
            margin-bottom: 0
        }
        .view-account .side-bar .side-menu .nav>li>a {
            display: block;
            color: #9499a3;
            padding: 10px 15px;
            padding-left: 30px
        }
        .view-account .side-bar .side-menu .nav>li>a:hover {
            background: #f9f9fb
        }
        .view-account .side-bar .side-menu .nav>li.active a {
            background: #f9f9fb;
            border-right: 4px solid #40babd;
            border-bottom: none
        }
        .theme-2 .view-account .side-bar .side-menu .nav>li.active a {
            border-right-color: #6dbd63
        }
        .theme-3 .view-account .side-bar .side-menu .nav>li.active a {
            border-right-color: #497cb1
        }
        .theme-4 .view-account .side-bar .side-menu .nav>li.active a {
            border-right-color: #ec6952
        }
        .view-account .side-bar .side-menu .nav>li .icon {
            font-size: 24px;
            vertical-align: middle;
            text-align: center;
            width: 40px;
            display: inline-block
        }
    }
    .module {
        border: 1px solid #f3f3f3;
        border-bottom-width: 2px;
        background: #fff;
        margin-bottom: 30px;
        position: relative;
        border-radius: 4px;
        background-clip: padding-box;
    }
    .module .module-footer {
        background: #fff;
        border-top: 1px solid #f3f3f7;
        padding: 15px;
    }
    .module .module-footer a {
        color: #9499a3;
    }
</style>
<script>
    $(document).ready(function(){
        // function loadCalendar(idCondominio) {


        var calendar = $('#calendario').fullCalendar({
            locale: 'es',
            defaultView: 'month',
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
                data: function (){
                    return {
                        id : $('#id-condominio :selected').val(),
                    }
                },
                dataType: 'json',
                error: function () {
                    alert('there was an error while fetching events!');
                },
                color: 'orange',   // a non-ajax option
                textColor: 'WHITE' // a non-ajax option
            },

            eventClick: function (event, jsEvent, view) {
                //$('#modalTitle').html(event.title);
                var list = "<ul><li>Título: " + event.title + "</li><li>Responsable: " + event.responsable + "</li><li>Condominio: " + event.condominio + "</li><li>Condómino: " + event.condomino + "</li><li>Amenidad: " + event.amenidad + "</li></ul>"
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
            selectable: true,
            selectHelper: true,
            select: function (start, end, allDay) {
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
            eventDrop: function (event, dayDelta, revertFunc, jsEvent, ui, view) {


                //updateForm(event);
            },
            eventOverlap: function (stillEvent, movingEvent) { // Para que no se traslapen los eventos
                return stillEvent.allDay && movingEvent.allDay;
            }

        });
        // }

    /*    $('#id-condominio').select2({
            cache: true,
            serverSide: true,
            placeholder:'Selecciona un condominio',
            dropdownAutoWidth: true,
            theme: "bootstrap4",
            ajax: {
                url:  '../../Condominio/getCondominios',
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
        }); */

        $('#id-condominio').on('change',function(e){
            var idCondominio = $(this).val()
            console.log('Condominio Id: ' + idCondominio);
            //loadCalendar(idCondominio);
            $('#calendario').fullCalendar('removeEvents');
            $('#calendario').fullCalendar( 'refetchEvents' );
        });

    });

</script>
<style>
    th.fc {
        color: #FFFFFF;
    }
</style>

