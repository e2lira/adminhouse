<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<?php

    echo $this->Html->css('fullcalendar.min');
//echo $this->Html->css('bootstrap');

echo $this->Html->script('moment');
//echo $this->Html->script('jquery.min');
echo $this->Html->script('fullcalendar.min');
echo $this->Html->script('jquery.validate.min');
echo $this->Html->script('locale/es');
?>
<!-- <script src="/js/locale/es.js"></script> -->

<div class="row">
    <div class="col-sm-12 col-sm-12 col-md-2" id="actions-sidebar">

        <div class="card">
            <div class="container">

                </ul>
            </div>
        </div>

    </div>


    <div class="col-sm-12 col-sm-12 col-md-10">
    <div class="page-header">
        <form >
        <fieldset>
            <legend><?= __('Filtro de condominio') ?></legend>
            <?php
            echo $this->Form->input('id_condominio',['type' => 'select',
                'options' => '',
                'label' => 'Condominios',
                'required' => 'required',
                'empty' => 'Selecciona']);

            ?>

        </fieldset>

        <?= $this->Form->end() ?>
    </div>
        <div class="container-fluid">
            <legend><?= __('Eventos') ?></legend>
            <div class="row">
                <div id="calendario"></div>
            </div>

        </div>

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
                    'options' => '',
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
    </div>
   <!-- <div class="col-sm-12 col-sm-12 col-md-12"> -->






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

        $('#id-condominio').select2({
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
        });

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
