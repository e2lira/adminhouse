<div class="reporte" style="margin: 20px;">
    <div class="row">
        <div class="col-md-4">
        </div>
    <center>
            <label class="titulo"><?php echo $condominio['nombre_condominio'] ?></label>
            <br>
            <label">Informe financiero</label>
            <br>
            <label>Año: <?php echo $time ?></label>
    </center>
    </div>
    <br><br>
    <table class="table table-striped table-hover table-sm" id="tableFinalAmount">
        <thead>
        <tr>
            <th scope="col">Concepto</th>
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
        <?php foreach ($saldo as $saldo): ?>
            <tr style="background-color: rgb(143,188,255);">
                <td>Saldo inicial</td>
                <td><?= $this->Number->format($saldo['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($saldo['junio'], [
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
                <td><?= $this->Number->format($its['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($its['junio'], [
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
    <br>
    <br>
    <h5>GASTOS VARIABLES</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col">Concepto<span style="opacity: 0%">dsssdsssd</span></th>
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
            <tr style="background-color: #fde5b1;">
                <td>Total g. variables<span style="opacity: 0%">dsss</span> </td>

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
    <br>
    <br>
    <h5>GASTOS CAJA CHICA</h5>
    <table class="table table-striped table-hover  table-sm" id="tableUsers">
        <thead>
        <tr>
            <th scope="col">Concepto<span style="opacity: 0%">dsssdsssd</span></th>
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
        <?php foreach ($caja as $caja): ?>
            <tr>
                <td><?= h($caja['concepto']) ?></td>
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
            <th scope="col">Concepto</th>
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
        <?php foreach ($cajaTotal as $cajaTotal): ?>
            <tr style="background-color: #fde5b1;">
                <td>Total <span style="color: white; opacity: 0%">caja chisssssss</span></td>
                <td><?= $this->Number->format($cajaTotal['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($cajaTotal['junio'], [
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
            <th scope="col">Concepto</th>
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Total</th>
        </thead>
        <tbody>
        <tr style="background-color: rgb(246,241,203);">
            <td>Total de gastos<span style="opacity: 0%">dsssdsd</span></td>
            <td><?= $this->Number->format($grandTotal['enero'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['febrero'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['marzo'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['abril'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['mayo'], [
                    'locale' => 'en_US',
                    'places' => 2,
                    'before' => '$'
                ]) ?>
            </td>
            <td><?= $this->Number->format($grandTotal['junio'], [
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
    <table class="table table-striped table-hover table-sm" id="tableUsers">
        <thead>
        <tr style="border-top: solid 5px white;">
            <th scope="col">Concepto</th>
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
        <?php foreach ($finalAmount as $finalAmount): ?>
            <tr style="background-color: #cceae7;">
                <td>Saldo al cierre</td>
                <td><?= $this->Number->format($finalAmount['enero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($finalAmount['febrero'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($finalAmount['marzo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($finalAmount['abril'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($finalAmount['mayo'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?>
                </td>
                <td><?= $this->Number->format($finalAmount['junio'], [
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
            <tr style="background-color: #fde5b1;">
                <td>Total </td>
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
                        <td><?= $this->Number->format($adTota['enero'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['febrero'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['marzo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['abril'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['mayo'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                        <td><?= $this->Number->format($adTota['junio'], [
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

        </div>
    </div>
</div>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 1px;
        text-align: left;
        border: 1px solid #ddd;
    }
</style>
