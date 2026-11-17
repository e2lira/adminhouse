<?php
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
                        <li><?= $this->Html->link(__('List Condomino'), ['controller' => 'Condomino', 'action' => 'index', $condomino['id_condominio']]) ?> </li>
                        <li><?= $this->Html->link(__('List Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?> </li>
                </ul>
            </div>
        </div>
</div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
    <?= $this->Form->create($condomino) ?>
    <fieldset>
        <legend>Editar Condomino</legend>
        <?php
        echo $this->Form->control('saldo_inicial',['label'=>'Saldo inicial a favor']);
        echo $this->Form->control('numero_departamento');
        echo $this->Form->control('residente_condominio');
        echo $this->Form->control('numero_contacto');
        echo $this->Form->input('correo_contacto');
        echo $this->Form->input('rfc', ['value'=>'RFC: ']);
        echo $this->Form->input('correo_prop');
        echo $this->Form->input('correo_prop2');
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
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
 </div>
</div>
<style>
    .btn-default{
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>


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
