<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>


<div class="jumbotron">
    <h1 class="display-4">Datos del visitante.</h1>
    <div class="row">
        <div class="col-lg-6 mb-6 mb-lg-0" style="text-align: left">
            <label><?= __('Nombre') ?></label>
            <br>
            <label><?= __('Departamento') ?></label>
            <br>
            <label><?= __('Tipo Visita') ?></label>
            <br>
            <label><?= __('Auto') ?></label>
            <br>
            <label><?= __('Color') ?></label>
            <br>
            <label><?= __('Marca') ?></label>
            <br>
            <label><?= __('Placas') ?></label>
            <br>
            <label><?= __('Fecha') ?></label>
            <br>
            <label><?= __('Hora') ?></label>
            <br>
        </div>
        <div class="col-lg-6 mb-6 mb-lg-0" style="text-align: left">
            <label><?= h($visita['nombre']) ?></label>
            <br>
            <label><?= h($visita['departamento']) ?></label>
            <br>
            <label><?= h($visita['tipo_visita']) ?></label>
            <br>
            <label><?= h($visita['auto']) ?></label>
            <br>
            <label><?= h($visita['color']) ?></label>
            <br>
            <label><?= h($visita['marca']) ?></label>
            <br>
            <label><?= h($visita['placas']) ?></label>
            <br>
            <label><?= h($visita['fecha']) ?></label>
            <br>
            <label><?= h($visita['hora']) ?></label>
            <br>
        </div>
    </div>
    <br>

</div>
