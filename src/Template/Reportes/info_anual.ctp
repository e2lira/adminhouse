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
            <img src="<?php echo '/../img/'. $condominio['logo'] ?>" alt="<?php echo '/', $condominio['logo'] ?>" style="width:300px;">
        </div>
        <div class="col-md-4">
            <label class="titulo"><?php echo $condominio['nombre_condominio'] ?></label>
        </div>
        <div class="col-md-2">
            <label">Informe financiero</label>
        </div>
        <div class="col-md-2">
            <label>Año: <?php echo $time ?></label>
        </div>
    </div>
    <br><br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col">Saldo</th>
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($saldo as $sald): ?>
            <tr>
                <td>Saldo Inicial:</td>

                <td><?= $this->Number->format($sald['saldo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cuotas as $cuota): ?>
            <tr>
                <td>Ingreso por Cuotas de Mantenimiento:</td>

                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($sacmex as $sac): ?>
            <tr>
                <td>Ingreso Pago SACMEX:</td>

                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sac['total'], [
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
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($its as $it): ?>
            <tr style="background-color: #c4e3f3;">
                <td>Total Ingresos:</td>

                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fijos as $fijo): ?>
            <tr>
                <td><?= h($fijo['categoria']) ?></td>
                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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
            <th scope="col" style="color: white;">CondominoasDasdasDasdsiss</th>
            <th scope="col" style="color: white;">Enero</th>
            <th scope="col" style="color: white;">Febrero</th>
            <th scope="col" style="color: white;">Marzo</th>
            <th scope="col" style="color: white;">Abril</th>
            <th scope="col" style="color: white;">Mayo</th>
            <th scope="col" style="color: white;">Junio</th>
            <th scope="col" style="color: white;">Julio</th>
            <th scope="col" style="color: white;">Agosto</th>
            <th scope="col" style="color: white;">Septiembre</th>
            <th scope="col" style="color: white;">Octubre</th>
            <th scope="col" style="color: white;">Noviembre</th>
            <th scope="col" style="color: white;">Diciembre</th>
            <th scope="col" style="color: white;">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fij as $fij): ?>
            <tr>
                <td>Total:</td>

                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($gastos as $gasto): ?>
            <tr>
                <td><?= h($gasto['categoria']) ?></td>
                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
            <th scope="col" style="color: white;">CondominoasDasdasDasdssa</th>
            <th scope="col" style="color: white;">Enero</th>
            <th scope="col" style="color: white;">Febrero</th>
            <th scope="col" style="color: white;">Marzo</th>
            <th scope="col" style="color: white;">Abril</th>
            <th scope="col" style="color: white;">Mayo</th>
            <th scope="col" style="color: white;">Junio</th>
            <th scope="col" style="color: white;">Julio</th>
            <th scope="col" style="color: white;">Agosto</th>
            <th scope="col" style="color: white;">Septiembre</th>
            <th scope="col" style="color: white;">Octubre</th>
            <th scope="col" style="color: white;">Noviembre</th>
            <th scope="col" style="color: white;">Diciembre</th>
            <th scope="col" style="color: white;">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($gas as $gas): ?>
            <tr>
                <td>Total:</td>

                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gas['total'], [
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
            <th scope="col" style="color: white;">CondominoasDasdasDasdssa</th>
            <th scope="col" style="color: white;">Enero</th>
            <th scope="col" style="color: white;">Febrero</th>
            <th scope="col" style="color: white;">Marzo</th>
            <th scope="col" style="color: white;">Abril</th>
            <th scope="col" style="color: white;">Mayo</th>
            <th scope="col" style="color: white;">Junio</th>
            <th scope="col" style="color: white;">Julio</th>
            <th scope="col" style="color: white;">Agosto</th>
            <th scope="col" style="color: white;">Septiembre</th>
            <th scope="col" style="color: white;">Octubre</th>
            <th scope="col" style="color: white;">Noviembre</th>
            <th scope="col" style="color: white;">Diciembre</th>
            <th scope="col" style="color: white;">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($totGas as $totGa): ?>
            <tr style="background-color: #fff3cd;">
                <td>Total de Gastos:</td>
                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($totGa['total'], [
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
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pagos as $pago): ?>
            <tr>
                <td><?= h($pago['departamento']) ?></td>
                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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
            <th scope="col" style="color: white;">Julio</th>
            <th scope="col" style="color: white;">Agosto</th>
            <th scope="col" style="color: white;">Septiembre</th>
            <th scope="col" style="color: white;">Octubre</th>
            <th scope="col" style="color: white;">Noviembre</th>
            <th scope="col" style="color: white;">Diciembre</th>
            <th scope="col" style="color: white;">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pag as $pag): ?>
            <tr>
                <td>Total: </td>
                <td><?= $this->Number->format($sald['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['junio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($sald['diciembre'], [
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

    <h5>ADEUDOS</h5>
    <br>
    <div class="row">
        <div class="col-md-12">
            <label><b>Condomino</b></label>
            <br>
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
                    <th scope="col">Julio</th>
                    <th scope="col">Agosto</th>
                    <th scope="col">Septiembre</th>
                    <th scope="col">Octubre</th>
                    <th scope="col">Noviembre</th>
                    <th scope="col">Diciembre</th>
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
                        <td><?= $this->Number->format($sald['enero'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['febrero'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['marzo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['abril'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['mayo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['junio'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['julio'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['agosto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['septiembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['octubre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['noviembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($sald['diciembre'], [
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
    }
</style>
<script>

    $("#buscar").on("click", function () {
        window.location.href ="http://localhost:8765/Reportes/info_anual/"+ <?= $condominio['id_condominio'] ?>+ '/' + $("#buscador").val();
    });

</script>
