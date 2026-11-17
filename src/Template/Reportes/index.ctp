<center>
    <h3>Seleccione el reporte que desea generar</h3>
</center>
<br><br>
<div>
    <div class="card">
        <div class="container">
            <h4><b>Informe Financiero</b></h4>
            <?= $this->Html->link('Primer semestre', ['controller' => 'Reportes', 'action' => 'prim', 'id'=>$condominio['id_condominio']]) ?>
            <br>
            <?= $this->Html->link('Segundo semestre', ['controller' => 'Reportes', 'action' => 'seg', 'id'=>$condominio['id_condominio']]) ?>
            <br>
            <?= $this->Html->link('Anual', ['controller' => 'Reportes', 'action' => 'infoAnual', 'id'=>$condominio['id_condominio']]) ?>
        </div>
    </div>
    <div class="card">
        <div class="container">
            <h4><b>Estado de Cuenta</b></h4>
            <?= $this->Html->link('Departamento', ['controller' => 'Reportes', 'action' => 'departamento', 'id'=>$condominio['id_condominio']]) ?>
        </div>
    </div>
  <!--  <div class="card">
        <div class="container">
            <h4><b>Cierre de mes</b></h4>
            <?= $this->Html->link('Cierre', ['controller' => 'Reportes', 'action' => 'cierre', 'id'=>$condominio['id_condominio']]) ?>
        </div>
    </div> -->
    <div class="card">
        <div class="container">
            <h4><b>Recibos de mes</b></h4>
            <?= $this->Html->link('Generar Recibos del mes', ['controller' => 'Reportes', 'action' => 'nuevo', 'id'=>$condominio['id_condominio']]) ?>
            <br>
            <a href="/Reportes/lista/<?= h($condominio->id_condominio)?>"> Lista de reportes</b></a>
        </div>
    </div>
    <br>
</div>

<style>
    .btn {
        background-color: #1A5276;
    }
</style>
