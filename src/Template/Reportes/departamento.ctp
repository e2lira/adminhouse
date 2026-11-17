<div class="row">
        <div class="col-md-3">
            <input type="text" name="from_date" id="from_date" class="form-control" placeholder="Fecha de inicio" />
        </div>
        <div class="col-md-3">
            <input type="text" name="to_date" id="to_date" class="form-control" placeholder="Fecha final" />
        </div>
        <div class="col-md-5">
            <input type="text" name="depto" id="depto" class="form-control" placeholder="Cuenta" />
        </div>
        <div style="clear:both"></div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-6">
            <input type="button" value="Buscar" id="buscar">

            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                Generar
            </button>
        </div>
    </div>
    <br>
    <br>
    <div class="reporte">
        <div class="row">
            <div class="col-md-6">
                    <div class="col-md-4">
                        <label>Estado de cuenta</label>
                        <br>
                        <label class="titulo"><?php echo $condominio['nombre_condominio'] ?></label>
                        <br>
                        <label>Departamento: <?php echo $depto ?></label>
                    </div>
                </div>
            <div class="col-md-6">
                <img src="<?php echo '/../img/'. $condominio['logo'] ?>" alt="<?php echo '/', $condominio['logo'] ?>" style="width:300px; margin-top: -80px;">
            </div>
            </div>
            <br>
            <br>
        <h5>Saldo inicial</h5>
        <div class="row">
            <div class="col-md-12">
                <br>
                <table class="table table-striped table-hover table-sm" id="tableUsers">
                    <thead>
                    <tr>
                        <th scope="col">Concepto</th>
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
                    <?php if ($saldoCondomino): ?>
                        <?php foreach ($saldoCondomino as $saldo): ?>
                            <tr>
                                <td>Saldo a favor</td>
                                <td><?= $this->Number->format($saldo['enero'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['febrero'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['marzo'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['abril'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['mayo'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['junio'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['julio'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['agosto'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['septiembre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['octubre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['noviembre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['diciembre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="14" class="text-center">No hay datos disponibles para el saldo del departamento.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <br>
        <br>
        <h5>Pagos</h5>
        <div class="row">
            <div class="col-md-12">
                <br>
                <table class="table table-striped table-hover  table-sm" id="tableUsers">
                    <thead>
                    <tr>
                        <th scope="col">Concepto</th>
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
                    <?php foreach ($adeudosPagos as $pago): ?>
                        <tr>
                            <td><?= h($pago['concepto']) ?></td>
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
                            <td style="color: #e4606d;"><?= $this->Number->format($pago['total'], [
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
        <h5>Adeudos</h5>
        <div class="row">
            <div class="col-md-12">
                <br>
                <table class="table table-striped table-hover  table-sm" id="tableUsers">
                    <thead>
                    <tr>
                        <th scope="col">Concepto</th>
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
                            <td><?= h($adeudo['concepto']) ?></td>
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
            </div>
        </div>

        <h5>Saldo al cierre</h5>
        <div class="row">
            <div class="col-md-12">
                <br>
                <table class="table table-striped table-hover table-sm" id="tableUsers">
                    <thead>
                    <tr>
                        <th scope="col">Concepto</th>
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
                    <?php if ($saldoCondomino): ?>
                        <?php foreach ($saldoCondomino as $saldo): ?>
                            <tr>
                                <td>Saldo al cierre</td>
                                <?php if ($saldo != "" && !is_null($saldo)): ?>
                                    <td><?= $this->Number->format($saldo['enero'] ?? 0, [
                                            'locale' => 'en_US',
                                            'places' => 2,
                                            'before' => '$'
                                        ]) ?>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <?= $this->Number->format(($saldo['enero'] ?? 0) - 1 + ($pago['enero'] ?? 0) - ($adeudo['enero'] ?? 0), [
                                            'locale' => 'en_US',
                                            'places' => 2,
                                            'before' => '$'
                                        ]) ?>
                                    </td>
                                <?php endif; ?>


                                <td><?= $this->Number->format($saldo['febrero'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>


                                <td><?= $this->Number->format($saldo['marzo'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['abril'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['mayo'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['junio'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['julio'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['agosto'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['septiembre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['octubre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['noviembre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                                <td><?= $this->Number->format($saldo['diciembre'] ?? 0, [
                                        'locale' => 'en_US',
                                        'places' => 2,
                                        'before' => '$'
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="14" class="text-center">No hay datos disponibles para el saldo del departamento.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>



        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        ...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $("#buscar").on("click", function () {

         window.location.href ="http://localhost:8765/Reportes/departamento/"+ <?= $condominio['id_condominio'] ?>+ '/' + $("#from_date").val() + '/' + $("#to_date").val() + '/' + $("#depto").val();

        });



        $(document).ready(function(){
            $.datepicker.setDefaults({
                dateFormat: 'yy-mm-dd'
            });
            $(function(){
                $("#from_date").datepicker();
                $("#to_date").datepicker();
            });
        });
    </script>
