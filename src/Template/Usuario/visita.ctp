
<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<!------ Include the above in your HEAD tag ---------->
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
                            <li><a href="/Usuario/visita"><span class="fa fa-qrcode"></span> Visitas</a></li>
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
                        <div class="actions">
                            <button type="button" class="btn btn-link">
                                Mensajes <span class="badge badge-light" style="color: #e3e0b1;"><?= h($Query->count) ?></span>
                            </button>
                            <a href="/Usuario/ticket" class="btn btn-success"><i class="fa fa-plus" ></i> Levantar un ticket</a>
                        </div>
                    </div>
                    <div class="drive-wrapper drive-grid-view">
                        <div class="col-xs-12 col-sm-12 col-md-12 ">
                            <div class="row">
                                <div class="col-md-9">
                                    <h3><span class="fa fa-qrcode"></span> <?= __('Registro de visitas') ?> </h3>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 col-sm-12">
                                        <h3>
                                            <a href="/Usuario/registrar" class="btn btn-info"><i class="fa fa-qrcode" ></i> Registrar Visita</a>
                                        </h3>
                                        <table class="table table-striped table-hover  table-sm" id="tableUsers">
                                            <thead>
                                            <tr>
                                                <th scope="col"><?= $this->Paginator->sort('nombre') ?></th>
                                                <th scope="col"><?= $this->Paginator->sort('departamento') ?></th>
                                                <th scope="col"><?= $this->Paginator->sort('fecha') ?></th>
                                                <th scope="col"><?= $this->Paginator->sort('hora') ?></th>
                                                <th scope="col"><?= $this->Paginator->sort('tipo_visita') ?></th>
                                                <th scope="col" class="actions"><?= __('Actions') ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($visitas as $visita): ?>
                                                <tr>
                                                    <td><?= h($visita->nombre) ?></td>
                                                    <td><?= h($visita->departamento) ?></td>
                                                    <td><?= h($visita->fecha) ?></td>
                                                    <td><?= h($visita->hora) ?></td>
                                                    <td><?= h($visita->tipo_visita) ?></td>
                                                    <td class="actions">
                                                        <?= $this->Html->link(__('Qr'), ['action' => 'qrlist', $visita->id_visita]) ?>
                                                        <?= $this->Html->link(__('Enviar'), ['action' => 'view', $visita->id_visita]) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="paginator">
                                            <ul class="pagination">
                                                <?= $this->Paginator->first('<< ' . __('first')) ?>
                                                <?= $this->Paginator->prev('< ' . __('previous')) ?>
                                                <?= $this->Paginator->numbers() ?>
                                                <?= $this->Paginator->next(__('next') . ' >') ?>
                                                <?= $this->Paginator->last(__('last') . ' >>') ?>
                                            </ul>
                                            <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

        </section>
    </div>

</div>
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
            <?= $this->Form->button(__('Guardar')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>

</div>
<style>
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
