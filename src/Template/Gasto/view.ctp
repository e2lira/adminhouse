<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Gasto $gasto
 */
?>
<div class="row">
    <button onclick="window.print()">Imprimir</button>
</div>
<br>

<div style="border: 1px solid black; border-radius: 15px;">

    <div class="row" style="margin-top: 40px;">
        <div class="col-md-10">
            <h5 style="margin-left: 50px;">POLIZA DE CHEQUE</h5>

        </div>
        <div class="col-md-2">
            <label><?= h($gasto->fecha_pago) ?></label>
        </div>
        <br>
        <br>
        <br>
        <div class="col-md-10">
        </div>

        <div class="col-md-2">
            <label>$ <?= h($gasto->gasto)?></label>
        </div>
        <div class="col-md-12">
            <center>
                <b><label><?= h($gasto->monto_letra) ?></label></b>
            </center>
        </div>
        <div class="col-md-10">

        </div>
        <br>

        <div class="col-md-2">
            <br>
            <br>
            <label>CHEQUE:</label>
        </div>

    </div>



</div>
<br>
<br>
<div>
    <div class="row">

    <div class="col-md-5 " style="border: 1px solid black; border-radius: 15px; margin-left: 12px">
        <h6>CONCEPTO DE PAGO:</h6>
        <br>
        <label><?= h($gasto->concepto) ?></label>
    </div>
        <div class="col-md-1">

        </div>
    <div class="col-md-5" style="border: 1px solid black; border-radius: 15px;">
        <h6>FIRMA CHEQUE RECIBIDO:</h6>

    </div>
</div>
</div>
<br>
<br>
<div style="border: 1px solid black; margin-left: 12px; height: 600px;">

</div>
<br>
<br>
<div class="row">
    <div class="col-md-10">
    </div>
    <div class="col-md-2">
    <label>Total $<?= $this->Number->format($gasto->gasto) ?></label>
    </div>

</div>
<br>
<br>
<div class="row">
    <div class="col-md-2" style="border: 1px solid black; border-radius: 15px; height: 100px;">
       <label>Elabora</label>
    </div>
    <div class="col-md-2" style="border: 1px solid black; border-radius: 15px; height: 100px;">
        <label>Revisa</label>
    </div>
    <div class="col-md-2" style="border: 1px solid black; border-radius: 15px; height: 100px;">
        <label>Autoriza</label>
    </div>
    <div class="col-md-2" style="border: 1px solid black; border-radius: 15px; height: 100px;">
        <label>Auxiliares</label>
    </div>
    <div class="col-md-2" style="border: 1px solid black; border-radius: 15px; height: 100px;">
        <label>Diario</label>
    </div>
    <div class="col-md-2" style="border: 1px solid black; border-radius: 15px; height: 100px;">
        <label>Poliza Num.</label>
    </div>

</div>
<br>
<br>
