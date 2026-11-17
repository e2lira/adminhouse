<style>
    @media print {
        #head {
            display: none;
        }
    }
</style>
<div class="row" id="head" name="head">
    <div class="col-md-3">
        <div class="input-group" style="margin-top: 15px;">
            <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
            <input id="buscador" type="number" class="form-control" name="buscar" min="2020" placeholder="Buscar fecha...">
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
            <label>Año: <?php echo $time ?></label>
        </div>
    </div>
    <br><br>
    <table class="table table-striped table-hover table-sm" id="tableFinalAmount">
        <thead>
        <tr>
            <th scope="col">Concepto</th>
            <th scope="col">julio</th>
            <th scope="col">agosto</th>
            <th scope="col">septiembre</th>
            <th scope="col">octubre</th>
            <th scope="col">noviembre</th>
            <th scope="col">diciembre</th>
            <th scope="col">Total</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($saldo as $saldo): ?>
            <tr style="background-color: rgb(143,188,255);">
                <td>Saldo inicial</td>
                <td><?= $this->Number->format($saldo['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['diciembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['total'], [
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
        <tr>
            <th scope="col">Tipo de cuota</th>
            <th scope="col">julio</th>
            <th scope="col">agosto</th>
            <th scope="col">septiembre</th>
            <th scope="col">octubre</th>
            <th scope="col">noviembre</th>
            <th scope="col">diciembre</th>
            <th scope="col">Total</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cuotas as $cuota): ?>
            <tr>
                <td><?= h($cuota['tipo']) ?></td>

                <td><?= $this->Number->format($cuota['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cuota['diciembre'], [
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
        <tr style="border-top: solid 5px white;">
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
        <?php foreach ($its as $its): ?>
            <tr style="background-color: #d1fad3;">
                <td>Total <span style="color: white; opacity: 0%">caja chiddddsssss</span></td>
                <td><?= $this->Number->format($its['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['diciembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['total'], [
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
    <h5>GASTOS FIJOS</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col"></th>
            <th scope="col">julio</th>
            <th scope="col">agosto</th>
            <th scope="col">septiembre</th>
            <th scope="col">octubre</th>
            <th scope="col">noviembre</th>
            <th scope="col">diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fijos as $fijo): ?>
            <tr>
                <td><?= h($fijo['categoria']) ?></td>
                <td><?= $this->Number->format($fijo['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fijo['diciembre'], [
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
    <br>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
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
        <?php foreach ($fij as $fij): ?>
            <tr style="background-color: #fde5b1;">
                <td>Total <span style="color: white; opacity: 0%">caja chiddddsssss</span></td>
                <td><?= $this->Number->format($fij['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($fij['diciembre'], [
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
    <br>
    <br>
    <h5>GASTOS VARIABLES</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col">Concepto<span style="opacity: 0%">dsssdsssd</span></th>
            <th scope="col">julio</th>
            <th scope="col">agosto</th>
            <th scope="col">septiembre</th>
            <th scope="col">octubre</th>
            <th scope="col">noviembre</th>
            <th scope="col">diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($gastos as $gasto): ?>
            <tr>
                <td><?= h($gasto['categoria']) ?></td>
                <td><?= $this->Number->format($gasto['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($gasto['diciembre'], [
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
            <tr style="background-color: #fde5b1;">
                <td>Total g. variables<span style="opacity: 0%">dsss</span> </td>

                <td><?= $this->Number->format($varia['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($varia['diciembre'], [
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
    <br>
    <br>
    <h5>GASTOS CAJA CHICA</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col">Concepto<span style="opacity: 0%">dsssdsssd</span></th>
            <th scope="col">julio</th>
            <th scope="col">agosto</th>
            <th scope="col">septiembre</th>
            <th scope="col">octubre</th>
            <th scope="col">noviembre</th>
            <th scope="col">diciembre</th>
            <th scope="col">Total</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($caja as $caja): ?>
            <tr>
                <td><?= h($caja['concepto']) ?></td>
                <td><?= $this->Number->format($caja['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($caja['diciembre'], [
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
        <?php foreach ($cajaTotal as $cajaTotal): ?>
            <tr style="background-color: #fde5b1;">
                <td>Total <span style="color: white; opacity: 0%">caja chisssssss</span></td>
                <td><?= $this->Number->format($cajaTotal['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['diciembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['total'], [
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
    <table class="table table-striped table-hover table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
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
        <tr style="background-color: rgb(246,241,203);">
            <td>Total de gastos<span style="opacity: 0%">dsssdsd</span></td>
            <td><?= $this->Number->format($grandTotal['julio'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['agosto'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['septiembre'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['octubre'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['noviembre'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['diciembre'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['total'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
        </tr>
        </tbody>
    </table>
    <br>
    <br>
    <br>
    <br>
    <h5>CUOTAS</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col">Condomino</th>
            <th scope="col">julio</th>
            <th scope="col">agosto</th>
            <th scope="col">septiembre</th>
            <th scope="col">octubre</th>
            <th scope="col">noviembre</th>
            <th scope="col">diciembre</th>
            <th scope="col">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pagos as $pago): ?>
            <tr>
                <td><?= h($pago['departamento']) ?></td>
                <td><?= $this->Number->format($pago['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pago['diciembre'], [
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
            <th scope="col" style="color: white;">julio</th>
            <th scope="col" style="color: white;">agosto</th>
            <th scope="col" style="color: white;">septiembre</th>
            <th scope="col" style="color: white;">octubre</th>
            <th scope="col" style="color: white;">noviembre</th>
            <th scope="col" style="color: white;">diciembre</th>
            <th scope="col" style="color: white;">Totales</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pag as $pag): ?>
            <tr style="background-color: #fde5b1;">
                <td>Total </td>
                <td><?= $this->Number->format($pag['julio'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['agosto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['septiembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['octubre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['noviembre'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($pag['diciembre'], [
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
                    <th scope="col">julio</th>
                    <th scope="col">agosto</th>
                    <th scope="col">septiembre</th>
                    <th scope="col">octubre</th>
                    <th scope="col">noviembre</th>
                    <th scope="col">diciembre</th>
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
                        <td><?= $this->Number->format($adeudo['julio'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['agosto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['septiembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['octubre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['noviembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adeudo['diciembre'], [
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
            <br>
            <br>
            <table class="table table-striped table-hover  table-sm" id="tableUsers">
                <thead>
                <tr style="border-top: solid 5px white;">
                    <th scope="col"></th>
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
                <?php foreach ($adTota as $adTota): ?>
                    <tr style="background-color: #fde5b1;">
                        <td>Total <span style="color: white; opacity: 0%">cajssa</span></td>
                        <td><?= $this->Number->format($adTota['saldo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['julio'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['agosto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['septiembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['octubre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['noviembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['diciembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['total'], [
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
            <table class="table table-striped table-hover table-sm" id="tableUsers">
                <thead>
                <tr style="border-top: solid 5px white;">
                    <th scope="col">Concepto</th>
                    <th scope="col">julio</th>
                    <th scope="col">agosto</th>
                    <th scope="col">septiembre</th>
                    <th scope="col">octubre</th>
                    <th scope="col">noviembre</th>
                    <th scope="col">diciembre</th>
                    <th scope="col">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($finalAmount as $finalAmount): ?>
                    <tr style="background-color: #cceae7;">
                        <td>Saldo al cierre</td>
                        <td><?= $this->Number->format($finalAmount['julio'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($finalAmount['agosto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($finalAmount['septiembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($finalAmount['octubre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($finalAmount['noviembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($finalAmount['diciembre'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($finalAmount['total'], [
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
<script>
    document.getElementById("buscar").onclick = function() {myFunction()};

    function myFunction() {
        window.location.href ="http://localhost:8765/reportes/informe/"+ <?= $condominio['id_condominio'] ?>+ '/' + $("#buscador").val();

    }
</script>
