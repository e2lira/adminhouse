<div class="row" id="cabecera">
    <div class="col-md-1">
        <div class="input-group" style="margin-top: 15px;">
        </div>
    </div>
    <div class=" col-md-7" style="background-color: #f2f2f2; border-radius: 15px;">
       <div class="row">
        <div class="col-md-6">
        <label for="cars">Selecciona un mes:</label>
        <select name="meses" id="mes" class="form-select" aria-label="Default select example" style="margin-right: 20px; ">
            <option value="1">Enero</option>
            <option value="2">Febrero</option>
            <option value="3">Marzo</option>
            <option value="4">Abril</option>
            <option value="5">Mayo</option>
            <option value="6">Junio</option>
            <option value="7">Julio</option>
            <option value="8">Agosto</option>
            <option value="9">Septiembre</option>
            <option value="10">Octubre</option>
            <option value="11">Noviembre</option>
            <option value="12">Diciembre</option>
        </select>
        </div>
        <div class="col-md-6">
        <label for="cars">Selecciona un año:</label>
        <select name="anio" id="anio" class="form-select">
            <option value="1989">1989</option>
            <option value="1990">1990</option>
            <option value="1991">1991</option>
            <option value="1992">1992</option>
            <option value="1993">1993</option>
            <option value="1994">1994</option>
            <option value="1995">1995</option>
            <option value="1996">1996</option>
            <option value="1997">1997</option>
            <option value="1998">1998</option>
            <option value="1999">1999</option>
            <option value="2000">2000</option>
            <option value="2001">2001</option>
            <option value="2002">2002</option>
            <option value="2003">2003</option>
            <option value="2004">2004</option>
            <option value="2005">2005</option>
            <option value="2006">2006</option>
            <option value="2007">2007</option>
            <option value="2008">2008</option>
            <option value="2009">2009</option>
            <option value="2010">2010</option>
            <option value="2011">2011</option>
            <option value="2012">2012</option>
            <option value="2013">2013</option>
            <option value="2014">2014</option>
            <option value="2015">2015</option>
            <option value="2016">2016</option>
            <option value="2017">2017</option>
            <option value="2018">2018</option>
            <option value="2019">2019</option>
            <option value="2020">2020</option>
            <option value="2021">2021</option>
            <option value="2022">2022</option>
        </select>
        </div>
           <br>
        <input type="button" value="Buscar" id="buscar">
        <br>
       </div>
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
        <div class="col-md-6">
            <label>Movimientos del Mes de: </label>   <label><?php echo $month.'-'.$time ?></label>
            <br>
            <label><b><?php echo $condominio->nombre_condominio ?></b></label>
        </div>
        <div class="col-md-6">
            <img src="<?php echo '/../img/'. $condominio['logo'] ?>" alt="<?php echo '/', $condominio['logo'] ?>" style="width:300px;">
        </div>
    </div>
    <br>
    <br>
    <div class="row" style="border: solid 1px black;">
        <div class="col-md-7">
            <label><b>Ingresos</b></label>
            <table class="table table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col">Depto</th>
                    <th scope="col">Saldo Inicial</th>
                    <th scope="col">Cuota del mes</th>
                    <th scope="col">Descuento por pronto pago</th>
                    <th scope="col">Pago</th>
                    <th scope="col">Aclaración</th>
                    <th scope="col">Saldo Final</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($cuotas as $cuot): ?>
                <tr>
                    <td><?= h($cuot['condomino']) ?></td>
                    <td><?= $this->Number->format($cuot['cuota'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?>
                    </td>
                    <td><?= $this->Number->format($cuot['descuento'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?>
                    </td>
                    <td><?= $this->Number->format($cuot['pago'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?>
                    </td>
                    <td><?= $this->Number->format($cuot['aclaracion'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?>
                    </td> <td><?= $this->Number->format($cuot['final'], [
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
        <div class="col-md-5">
            <label><b>Egresos</b></label>
            <br>
            <table class="table table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col">Gastos fijos</th>
                    <th scope="col">Monto</th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($fijos as $fijos): ?>
                    <tr>
                        <td><?= h($fijos['categoria']) ?></td>
                        <td><?= $this->Number->format($fijos['gasto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br><br>
            <table class="table table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col">Gastos variables</th>
                    <th scope="col">Monto</th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($variables as $variables): ?>
                    <tr>
                        <td><?= h($fijos['categoria']) ?></td>
                        <td><?= $this->Number->format($variables['gasto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br><br>
            <table class="table table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col">Gastos varios</th>
                    <th scope="col">Monto</th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($varios as $varios): ?>
                    <tr>
                        <td><?= h($varios['categoria']) ?></td>
                        <td><?= $this->Number->format($varios['monto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br><br>
            <table class="table table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>
                    <th scope="col">Gastos varios</th>
                    <th scope="col">Monto</th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($equip as $equip): ?>
                    <tr>
                        <td><?= h($equip['categoria']) ?></td>
                        <td><?= $this->Number->format($equip['monto'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br><br><br>
            </table>
            <br><br>
        </div>
        <br><br>
        <br><br>
        <!----------------------------------- Total ingresos y egresos ------------------------------->
        <div class="col-md-6">
            <table class="table table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($pt as $pt): ?>
                    <tr>
                        <td>Total Ingresos:</td>
                        <td><?= $this->Number->format($pt['ing'], [
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
        <div class="col-md-6">
            <table class="table table-hover  table-sm" id="tableUsers">
                <thead>
                <tr>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($gt as $gt): ?>
                    <tr>
                        <td>Total Egresos:</td>
                        <td><?= $this->Number->format($gt['monto'], [
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
    <div class="row" style="border: solid 1px black;">
        <div class="col-md-6">
            <label><b>Saldos</b></label>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <br>
                    <label><b>Saldo Inicial del mes</b></label>
                    <br>
                    <label>Ingresos</label>
                    <br>
                    <label>Egresos</label>
                    <br>
                    <label><b>Saldo final del mes</b></label>
                </div>
                <div class="col-md-6">
                    <br>

                    <?php

                    if($siTotal=''){
                        echo '<label>'.$this->Number->format($stotal['saldo'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]). '</label>';
                    }else

                    ?>


                    <br>
                    <label><?= $this->Number->format($intotal['in'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?> </label>
                    <br>
                   <label><?= $this->Number->format($gatotal['ga'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                       ]) ?> </label>
                    <br>
                    <label><?=

                        $this->Number->format($stotal->saldo + $intotal->in - $gatotal->ga,
                            [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ])?> </label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <label><b>Conciliación</b></label>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <br>
                    <label><b>Saldo en Bancos</b></label>
                    <br>
                    <label>Depósitos en tránsito</label>
                    <br>
                    <label>Cheques en tránsito</label>
                    <br>
                    <label><b>Saldo Final</b></label>
                </div>
                <div class="col-md-6">
                    <br>
                    <label><?= $this->Number->format($stotal['saldo'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?> </label>
                    <br>
                    <label><?= $this->Number->format($dttotal['dt'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?> </label>
                    <br>
                    <label><?= $this->Number->format($chttotal['cht'], [
                            'locale' => 'en_US',
                            'places' => 2,
                            'before' => '$'
                        ]) ?> </label>
                    <br>
                    <label><?=
                        $this->Number->format($dttotal->dt + $chttotal->cht,
                           [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ])?> </label>
                       </label>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $("#buscar").on("click", function () {

        console.log(Date.now());
        window.location.href ="http://localhost:8765/Reportes/cierre/"+ <?= $condominio['id_condominio'] ?>+ '/' + $("#mes").val() + '/' + $("#anio").val();

    });
</script>
