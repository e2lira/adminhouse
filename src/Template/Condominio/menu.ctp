<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" />

<div>
 <center>
     <img src="<?php echo '/../files/Condominio/logo/'.$condominio['logo'] ?>" alt="<?php echo '/', $condominio['logo'] ?>" style="width: 300px;">
     <h3 style="color: #6c757d"><?= h($condominio->nombre_condominio)?></h3>
 </center>
</div>
<br>

<div class="container">
    <h4>Operación</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
        <a href="/Pago/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
        <div class="col">
            <div class="card radius-10 border-start border-0 border-3 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-secondary">Tipo de operación</p>
                            <h4 class="my-1 text-info">
                                Ingresos
                            </h4>
                        </div>
                        <div class="widgets-icons-2 rounded-circle bg-gradient-scooter text-white ms-auto"><i class="fa fa-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </a>

        <a href="/Gasto/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
        <div class="col">
            <div class="card radius-10 border-start border-0 border-3 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-secondary">Tipo de operación</p>
                            <h4 class="my-1 text-info">
                                Egreso
                            </h4>
                        </div>
                        <div class="widgets-icons-2 rounded-circle bg-gradient-scooter text-white ms-auto"><i class="fa fa-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </a>

        <a href="/Cuotas/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de operación</p>
                                <h4 class="my-1 text-info">
                                    Cuotas
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-scooter text-white ms-auto"><i class="fa fa-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <h4>Administración</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
        <a href="/Condomino/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de Administración</p>
                                <h4 class="my-1 text-danger">Condominos</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="fa fa-clipboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Reportes/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de Administración</p>
                                <h4 class="my-1 text-danger">Reportes</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="fa fa-clipboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Supervision/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de Administración</p>
                                <h4 class="my-1 text-danger">Supervisión</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="fa fa-clipboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Comunicado/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de Administración</p>
                                <h4 class="my-1 text-danger">Comunicados</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="fa fa-clipboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Events/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de Administración</p>
                                <h4 class="my-1 text-danger">Eventos</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="fa fa-clipboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Recordatorio/recordatorio/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de Administración</p>
                                <h4 class="my-1 text-danger">Recordatorios</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="fa fa-clipboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <h4>Usuarios</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
        <a href="/Administrador/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Solicitudes de usuarios</p>
                                <h4 class="my-1 text-success">Usuarios</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class="fa fa-user-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Mensaje/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Solicitudes de usuarios</p>
                                <h4 class="my-1 text-success">Mensajes</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class="fa fa-user-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Administrador/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Solicitudes de usuarios</p>
                                <h4 class="my-1 text-success">Tickets</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class="fa fa-user-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Administrador/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Solicitudes de usuarios</p>
                                <h4 class="my-1 text-success">Visitas</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class="fa fa-user-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <h4>Información</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
        <a href="/Saldo-condominio/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Saldo Inicial</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Condominio/view/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Ver Detalle</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Condominio/edit/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Editar Info</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Reglamento/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Reglamentos</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Folios/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Folios</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Banco/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Banco</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Categoria/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Categoria</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Tipo-pago/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Tipo de Pago</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Tipo-gasto/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Tipo de Gasto</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Tipo-cuota/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Tipo de Cuota</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Tipo-visita/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Tipo de Visita</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Amenidad/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Amenidades</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Proveedores/add/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Proveedores</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
        <a href="/Bancaria/index/<?= h($condominio->id_condominio)?>" style="text-decoration: none;">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Tipo de información</p>
                                <h4 class="my-1 text-warning">Cuentas Bancarias</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    </div>



<style>
    body{
        margin-top:20px;
    }
    .radius-10 {
        border-radius: 10px !important;
    }

    .border-info {
        border-left: 5px solid  #0dcaf0 !important;
    }
    .border-danger {
        border-left: 5px solid  #fd3550 !important;
    }
    .border-success {
        border-left: 5px solid  #15ca20 !important;
    }
    .border-warning {
        border-left: 5px solid  #ffc107 !important;
    }


    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 0px solid rgba(0, 0, 0, 0);
        border-radius: .25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 6px 0 rgb(218 218 253 / 65%), 0 2px 6px 0 rgb(206 206 238 / 54%);
    }
    .bg-gradient-scooter {
        background: #17ead9;
        background: -webkit-linear-gradient(
            45deg
            , #17ead9, #6078ea)!important;
        background: linear-gradient(
            45deg
            , #17ead9, #6078ea)!important;
    }
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
        border-radius: 10px;
    }
    .rounded-circle {
        border-radius: 50%!important;
    }
    .text-white {
        color: #fff!important;
    }
    .ms-auto {
        margin-left: auto!important;
    }
    .bg-gradient-bloody {
        background: #f54ea2;
        background: -webkit-linear-gradient(
            45deg
            , #f54ea2, #ff7676)!important;
        background: linear-gradient(
            45deg
            , #f54ea2, #ff7676)!important;
    }

    .bg-gradient-ohhappiness {
        background: #00b09b;
        background: -webkit-linear-gradient(
            45deg
            , #00b09b, #96c93d)!important;
        background: linear-gradient(
            45deg
            , #00b09b, #96c93d)!important;
    }

    .bg-gradient-blooker {
        background: #ffdf40;
        background: -webkit-linear-gradient(
            45deg
            , #ffdf40, #ff8359)!important;
        background: linear-gradient(
            45deg
            , #ffdf40, #ff8359)!important;
    }
    a {
        text-decoration: none;
    }
</style>
