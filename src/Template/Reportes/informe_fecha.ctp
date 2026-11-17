<div class="row" id="cabecera">
    <div class="col-md-3">
        <div class="input-group" style="margin-top: 15px;">
            <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
            <input id="buscador" type="text" class="form-control" name="buscar" placeholder="Buscar fecha...">
            <input type="button" value="Buscar" id="buscar">
        </div>
    </div>
    <div class=" col-md-7">
    </div>
    <div class=" col-md-2">
        <a onclick="window.print()" style="color: #e5e5e5; margin-right: 10px;" class="btn btn-info"><i class="fa fa-print"></i> Imprimir</a>
    </div>
</div>
<br>
<br>
<br>
<div class="reporte" style="margin: 20px;">
    <div class="row">
        <div class="col-md-4">
            <img src="<?php echo '/../files/Condominio/logo/'. $condominio['logo'] ?>" alt="<?php echo '/', $condominio['logo'] ?>" style="width:300px;">
        </div>
        <div class="col-md-4">
            <label class="titulo"><?php echo $condominio['nombre_condominio'] ?></label>
        </div>
        <div class="col-md-2">
            <label">Informe financiero</label>
        </div>
        <div class="col-md-2">
            <label>Año: <?php echo $fecha ?></label>
        </div>
    </div>
    <br><br>
    <?php foreach ($saldo as $sald): ?>
    <?php endforeach; ?>

    <div id="saldo">

    </div>

    <br>
    <br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"> </th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cuotas as $cuota): ?>
            <tr>
                <td><?= h($cuota['tipo']) ?></td>

                <td><?= $this->Number->format($cuota['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($its as $it): ?>
            <tr style="background-color: #c4e3f3;">
                <td>Total Ingresos:</td>

                <td><?= $this->Number->format($it['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <br>
    <h5>GASTOS FIJOS</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fijos as $fijo): ?>
            <tr>
                <td><?= h($fijo['categoria']) ?></td>
                <td><?= $this->Number->format($fijo['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fij as $fij): ?>
            <tr>
                <td>Total gastos fijos:</td>

                <td><?= $this->Number->format($fij['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <br>
    <h5>GASTOS VARIABLES</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($gastos as $gasto): ?>
            <tr>
                <td><?= h($gasto['categoria']) ?></td>
                <td><?= $this->Number->format($gasto['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <br>

    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($varia as $varia): ?>
            <tr>
                <td>Total g. variables: </td>

                <td><?= $this->Number->format($varia['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>


    <br>
    <br>
    <h5>GASTOS CAJA CHICA</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($caja as $caja): ?>
            <tr>
                <td> Conceptos varios</td>
                <td><?= $this->Number->format($caja['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <br>
    <br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($totGas as $totGa): ?>
            <tr style="background-color: #fff3cd;">
                <td>Total de Gastos:</td>
                <td><?= $this->Number->format($totGa['enero']+$caja['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($totGa['febrero']+$caja['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($totGa['marzo']+$caja['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($totGa['abril']+$caja['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($totGa['mayo']+$caja['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($totGa['junio']+$caja['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($totGa['total']+$caja['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <br>
    <br>
    <br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
            <th scope="col" style="color: white;"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($totGas as $totGa): ?>
            <tr style="background-color: #b2dba1;">
                <td>Saldo cierre del mes:</td>
                <td><?= $this->Number->format($it['enero']+$sald['enero']-$totGa['enero']-$caja['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['febrero']+$sald['febrero']-$totGa['febrero']-$caja['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['marzo']+$sald['marzo']-$totGa['marzo']-$caja['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['abril']+$sald['abril']-$totGa['abril']-$caja['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['mayo']+$sald['mayo']-$totGa['mayo']-$caja['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['junio']+$sald['junio']-$totGa['junio']-$caja['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($it['total']+$sald['total']-$totGa['total']-$caja['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <br>
    <h5>CUOTAS</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col">Condomino</th>
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pagos as $pago): ?>
            <tr>
                <td><?= h($pago['departamento']) ?></td>
                <td><?= $this->Number->format($pago['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
            <th scope="col" style="color: white;">Condomino</th>
            <th scope="col" style="color: white;">Enero</th>
            <th scope="col" style="color: white;">Febrero</th>
            <th scope="col" style="color: white;">Marzo</th>
            <th scope="col" style="color: white;">Abril</th>
            <th scope="col" style="color: white;">Mayo</th>
            <th scope="col" style="color: white;">Junio</th>
            <th scope="col" style="color: white;">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pag as $pag): ?>
            <tr>
                <td>Total: </td>
                <td><?= $this->Number->format($pag['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['total'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <br>

    <h5 class="adeudos">ADEUDOS</h5>
    <label>Cuotas de mantenimiento</label>
    <br>
    <br>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col">Condomino</th>
                    <th scope="col">Saldo</th>
                    <th scope="col">Enero</th>
                    <th scope="col">Febrero</th>
                    <th scope="col">Marzo</th>
                    <th scope="col">Abril</th>
                    <th scope="col">Mayo</th>
                    <th scope="col">Junio</th>
                    <th scope="col">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($adeudos as $adeudo): ?>
                    <tr>
                        <td><?= h($adeudo['departamento']) ?></td>
                        <td><?= $this->Number->format($adeudo['saldo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['enero'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['febrero'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['marzo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['abril'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['mayo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['junio'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td style="color: #e4606d;"><?= $this->Number->format($adeudo['total'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <br>
    <br>

</div>
</div>

<style>
    .titulo{
        font-size: 20px;
    }
    @media print {
        #cabecera * {
            visibility: hidden;
        }
        .reporte, .reporte* {
            visibility: visible;
        }

        .adeudos{
            page-break-before: always;
        }
    }
</style>
<script>

    $("#buscar").on("click", function () {
        window.location.href ="http://localhost:8765/Reportes/informeFecha/"+ <?= $condominio['id_condominio'] ?>+ '/' + $("#buscador").val();
    });


    var content = "<table class='table table-striped table-hover  table-sm' id='tableUsers'>" +
        "<thead><tr><th scope='col'></th><th scope='col'>Enero</th><th scope='col'>Febrero</th><th scope='col'>Marzo</th><th scope='col'>Abril</th>"+
        "<th scope='col'>Mayo</th><th scope='col'>Junio</th></tr></thead>" +
        "<tbody>"+
        "<?php foreach ($saldo as $sald): ?>"
        +"<tr>"+
        "<td>Saldo Inicial:</td>"+
        "<td>"+
        "<?= $this->Number->format($sald['enero'], [
            'locale' => 'en_US',
            'places' => 2,
            'before' => '$'
        ]) ?>"+
        "</td>"+
        "<td>"+
        "<?= $this->Number->format($it['enero']+$sald['enero']-$totGa['enero']-$caja['enero'], [
            'locale' => 'en_US',
            'places' => 2,
            'before' => '$'
        ]) ?>"+
        "</td>"+
        "<td>"+
        "<?= $this->Number->format($it['febrero']+$sald['febrero']-$totGa['febrero']-$caja['febrero'], [
            'locale' => 'en_US',
            'places' => 2,
            'before' => '$'
        ]) ?>"+
        "</td>"+
        "<td>"+
        "<?= $this->Number->format($it['marzo']+$sald['marzo']-$totGa['marzo']-$caja['marzo'], [
            'locale' => 'en_US',
            'places' => 2,
            'before' => '$'
        ]) ?>"+
        "</td>"+
        "<td>"+
        "<?= $this->Number->format($it['abril']+$sald['abril']-$totGa['abril']-$caja['abril'], [
            'locale' => 'en_US',
            'places' => 2,
            'before' => '$'
        ]) ?>"+
        "</td>"+
        "<td>"+
        "<?= $this->Number->format($it['mayo']+$sald['mayo']-$totGa['mayo']-$caja['mayo'], [
            'locale' => 'en_US',
            'places' => 2,
            'before' => '$'
        ]) ?>"+
        "</td>"+
        "</tr>"+
        "<?php endforeach; ?>"+
        "</tbody>"+
        "</table>"

    ;
    document.getElementById('saldo').innerHTML = content;

</script>


