<?php
echo $this->Html->script('SweetAlertHelper.sweetalert2.min');

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condomino $condomino
 */
?>

<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Condominos'), ['controller' => 'Condomino', 'action' => 'index', 'id'=>$condominio['id_condominio']]) ?></li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="container">
                <h3>Condominio: <?= $condominio->nombre_condominio ?></h3>
                <ul style="color: #0f0f0f">
                    <li><span>Nombre:</span> <?= $condominio->nombre_condominio ?></li>
                    <li><span>Direccion:</span> <?= $condominio->direccion_condominio ?></li>
                    <li><span>Telefono:</span> <?= $condominio->telefono_condominio ?></li>
                    <li><span>No. Cuenta:</span> <?= $condominio->no_cuenta_condominio ?></li>
                    <li><span>Banco:</span> <?= $condominio->banco_cuenta ?></li>
                </ul>
            </div>
        </div>
        <br><br>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <div class="page-header">
            <?= $this->Form->create($condomino)?>
    <fieldset>
        <legend><?= __('Agregar Condomino') ?></legend>
        <br>
        <?php
            echo $this->Form->input('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
            echo $this->Form->input('numero_departamento');
            echo $this->Form->input('residente_condominio',['label'=>'Propietario Pincipal']);
            echo $this->Form->input('numero_contacto');
            echo $this->Form->input('correo_contacto');
            echo $this->Form->input('rfc', ['value'=>'RFC: ']);
            echo $this->Form->input('correo_prop', ['label'=>'Correo Propietario 2']);
            echo $this->Form->input('correo_prop2', ['label'=>'Correo Inquilino']);
            echo $this->Form->input('celular');
            echo $this->Form->input('celular_adicional');
            echo $this->Form->input('marca_auto');
            echo $this->Form->input('modelo_auto');
            echo $this->Form->input('color_auto');
            echo $this->Form->input('placas_auto');
            echo $this->Form->input('nota');

        ?>

        <input style="background-color: #E5E7E9; border-radius: 5px;" type="button" onclick="myFunction()" value="En Renta">
        <br><br>
        <div class="arrenda" id="arrenda" style="display: none;">
            <?php
            echo $this->Form->input('arrendatario');
            echo $this->Form->input('telefono_arrendatario');
            ?>
        </div>

    </fieldset>



    <?= $this->Form->button(__('Guardar')) ?>
    <?= $this->Form->end() ?>
  </div>
</div>

<script>
function myFunction() {
    var x = document.getElementById("arrenda");
    if (x.style.display === "none") {
        x.style.display = "block";
    } else {
        x.style.display = "none";
    }
}
</script>
