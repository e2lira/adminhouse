<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cuota $cuota
 */


    echo $this->Html->css('fullcalendar.min');
//echo $this->Html->css('bootstrap');

echo $this->Html->script('moment');
//echo $this->Html->script('jquery.min');
echo $this->Html->script('fullcalendar.min');
echo $this->Html->script('jquery.validate.min');


?>
    <div class="col-sm-12 col-sm-12 col-md-10">
        <div class="container-fluid">
            <div class="row">
                <br><br>
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 offset-xl-5 offset-lg-5 offset-md-5 offset-sm-3 offset-xs-2 imag">
                  <img src="../img/alpa_logo.png" style="height: 250px;"/>
                </div>
                <div class="offset-xl-2 offset-lg-2 offset-md-2">
                <br>
                    <h2 style="text-align: center">Bienvenido al Sistema de Administración de Condominios <b style="color: #820000">ALPA</b></h2>
                </div>
            </div>


        </div>

    </div>
<style>
    @media only screen and (max-width: 535px) {
        .imag{
            margin-left: 45px;
        }
    }

</style>