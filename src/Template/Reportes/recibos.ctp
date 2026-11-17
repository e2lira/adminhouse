<style type="text/css" media="print">

</style>

<div class="container">
    <button type="button" class="btn btn-info" onclick="javascript:window.print()"><i class="fa fa-print"></i> Imprimir</button>
    <br><br>
    <?php foreach ($condominio as $condominio){
    echo'<div class="uno" style="border: solid 1px black; border-radius: 10px; height: 50%; margin-bottom: 5px;">
        <div class="row" style="margin: 30px;">
            <div class="col-md-10">
                <h4>RECIBO DE PAGO</h4>
            </div>

            <div class="col-md-2">
                <label style="color: #e4606d">Folio: '. h($condominio['folio']).'</label>
                <img src="/img/kualitet.png" style="width: 150px;"  />

            </div>
        </div>
        <div class="row" style="margin: 50px;">
            <div class="col-md-10">
                <label>Condominio: '. h($condominio['nombre_condominio']) .'</label>
                <br>
                <label>'. h($condominio['rfc']) .'</label>
                <br>
                <label style="margin-right: 15px;">Casa: '. h($condominio['departamento']).'   </label> <label> '. h($condominio['residente']).'</label>
                <br>
                <label>'. h($condominio['rf']).'</label>
                <br>
                <label style="margin-right: 15px;">Cantidad: $'. h($condominio['pago']).' </label><label> ('. h($condominio['monto']).')</label>
                <br>
                <label>Por concepto de: '. h($condominio['concepto']).'</label>
                <br>
                <br>
                <label>La administración </label>
                <img src="/img/qrcliente.png" style="width: 150px;"  />
                <label>Fecha de pago: '. h($condominio['fecha']).'</label>

                <br>
                <center>
                    <label>CONDÓMINO</label>
                    <br>
                    <label>EL PAGO DE ESTE RECIBO NO EXIME DE ADEUDOS ANTERIORES</label>
                </center>


            </div>
        </div>
    </div> <br>';
}
    ?>
</div>

<style>
    label {
        font-size: 20px;
    }
</style>
