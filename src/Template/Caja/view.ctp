<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Gasto $gasto
 */
?>
<div class="row" style="margin-left: 15px;">
    <button onclick="window.print()">Imprimir</button>
</div>
<br>

<div style="border: 1px solid black; border-radius: 10px;">

    <div class="row" style="margin-top: 40px;">
        <div class="col-md-10">
            <h5 style="margin-left: 50px;">POLIZA DE CHEQUE</h5>

        </div>
        <div class="col-md-2">
            <label><?= $this->Time->format($caja['fecha'],'d MMMM Y') ?></label>

        </div>
        <br>
        <br>
        <br>
        <div class="col-md-10">
            <center>
                <label style="font-size: 22px;"> <?= h($caja->beneficiario)?></label>


            </center>
        </div>

        <div class="col-md-2">
            <label style="color: #6c757d;">Total:<b> $<?= $query2['sum']?></b></label>
        </div>
        <div class="col-md-12">
            <center>
                <b><label><?= h($caja->monto_letra) ?></label></b>
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
        <div class="" style="border: 1px solid black; border-radius: 15px; height: 150px; width: 98%; margin-left: 14px;">
            <br>
            <h6 style="margin-left: 10px;">FIRMA CHEQUE RECIBIDO:</h6>

        </div>
    </div>
</div>
<br>
<br>
<div style="border: 1px solid black; margin-left: 12px; height: 600px;">
    <h4 style="margin-left: 10px;"> Por concepto de:</h4>
    <div class="row">
        <div class="col-md-6">
            <?php if (!empty($conceptos)): ?>
                <ul>
                    <?php foreach($conceptos as $concepto){
                        echo "<li>".$concepto->concept."</li>";
                    }   ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <?php if (!empty($conceptos)): ?>
                <ul>
                    <?php foreach($conceptos as $concepto){
                        echo "<li> $".$concepto->gasto."</li>";
                    }   ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<br>
<br>
<div class="row">
    <div class="col-md-10">
    </div>
    <div class="col-md-2">
        <label style="color: #6c757d;">Total:<b> $<?= $query2['sum']?></b></label>

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
