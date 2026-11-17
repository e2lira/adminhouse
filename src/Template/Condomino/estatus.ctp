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
            <legend>Bloquear usuario </legend>
            <?php
            echo $this->Form->input('estatus',['type' => 'select',
                'options' => ['ACTIVO'=>'ACTIVO', 'BLOQUEADO'=>'BLOQUEADO'],
                'empty' => 'Selecciona']);
            ?>
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
