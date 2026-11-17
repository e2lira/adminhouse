<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condominio[]|\Cake\Collection\CollectionInterface $condominio
 */
?>

<div class="row">
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalCenterTitle">Descripci��n de los iconos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="font-size: 20px; ">
                    <i class='fa fa-eye'></i> <label>Ver</label><br><br>
                    <i class='fa fa-edit'></i> <label>Edit</label><br><br>
                    <i class='fa fa-address-book'></i> <label>Condomino</label><br><br>
                    <i class='fa fa-credit-card'></i> <label>Pago</label><br><br>
                    <i class='fa fa-dollar'></i> <label>Gasto</label><br><br>
                    <i class='fa fa-wpforms'></i> <label>Cuota</label><br><br>
                    <i class='fa fa-clipboard'></i> <label>Bitacora</label><br><br>
                    <i class='fa fa-trash'></i> <label>Borrar</label>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <br>
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
    <center>
    <h2><i class="fa fa-building"></i> Condominios</h2>
    </center>
    <div class="container">
        <?php foreach ($condominio as $condominio): ?>

            <a href="Condominio/menu/<?= h($condominio['id_condominio'])?>" class="btn btn-1"><i class='fa fa-building'></i><b> <?= h($condominio['nombre_condominio']) ?></b></a>

        <?php endforeach; ?>

    </div>



</div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalUsers" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desea usted eliminar el condominio?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button id="borrar" type="button" class="btn btn-danger" >Eliminar</button>
            </div>
        </div>

    </div>
</div>

<script>
    $(document).ready(function(){
        console.log('Ya estoy JQuery');
        $('#tableUsers button[type="button"]').each(function (index, element) { //index: N. objetos dentro del table, element: objeto que se esta buscando
            console.log("Row ..........");
            $(element).on('click', function () {
                var button = $(this);
                $('#modalUsers').on('show.bs.modal', function (event) {
                    var idCon = button.attr('data-id-condominio');
                    console.log('Condominio' + idCon);
                    $(event.currentTarget).find('button[id="borrar"]').attr('data-id', idCon);
                    $('#modal').data('data-id', idCon);
                    var modal  = $(this);
                    modal.find('.modal-body').html(button.data('nombre-condominio'));
                });
                $('#modalUsers').modal('show');
            });
        });
    });


    $('#borrar').on('click', function () {
        var id = $(this).attr('data-id');
        console.log('Borrar');
        $.ajax({
            method: "get",
            url: '/condominio/borrar/' + id,
            dataType: 'json',
            success: function(msg) {
                result = msg[0];
                console.log(result.message);
                if (result.result === 'ok') {
                    delete(id);
                    window.location.reload();
                }
            }
        });
    });

    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
<style>
    label{
        color: #843534;
    }

    /* <- Demo Stuff Start */

    body{
        font-family: 'Montserrat', sans-serif;
        margin:0;
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        align-content: center;
        flex-wrap: wrap;
        width: 80vw;
        margin: 0 auto;
        min-height: 100vh;
        color: #6c757d !important;

    }
    .btn {
        flex: 1 1 auto;
        margin: 10px;
        padding: 30px;
        text-align: center;
        text-transform: uppercase;
        transition: 0.5s;
        background-size: 200% auto;
        color: #6c757d !important;

        /* text-shadow: 0px 0px 10px rgba(0,0,0,0.2);*/
        box-shadow: 0 0 20px #eee;
        border-radius: 10px;
    }

    /* Demo Stuff End -> */

    /* <- Magic Stuff Start */

    .btn:hover {
        background-position: right center; /* change the direction of the change here */
    }

    .btn-1 {
        background-image: linear-gradient(to right, #edca72 0%, #f5dea4 51%, #fcf4de 100%);
    }

</style>
