<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Excel $excel
 */
?>
<?= $this->Flash->render() ?>
<div class="container-fluid">
    <?= $this->fetch('content') ?>
</div>
<center>
    <button type="button" class="btn btn-primary col-md-6" data-toggle="modal" data-target="#reg">
        Cargar registros iniciales
    </button>
</center>
<br><br><br>
<div class="row" style="border: solid 1px #3875d7; margin: 10px; border-radius: 15px;">
    <div class="col-md-12">
        <br>
    </div>
    <div class="col-md-4 columns content" style="margin-top: 30px;">
    <?= $this->Form->create($excel, ['type' => 'file']) ?>
    <fieldset>
        <legend><?= __('Cargar Excel') ?></legend>
        <?php
        echo $this->Form->control('archivo', ['type' => 'file']);
        echo $this->Form->input('id_user', ['type' => 'hidden', 'value' => $this->request->session()->read('Auth.User.id_user')]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
        <br>
        <br>
        <br>
</div>

    <div class="excel col-md-4 columns content" style="margin-top: 30px;">
        <h3><?= __('Guardar Registros') ?></h3>
        <a href="../../files/Excel/archivo/<?= $shreed->archivo ?>"   style="color: #6f42c1"><?= $shreed->archivo ?></a>
        <?= $this->Form->create(null, ['url'=> ['action' => 'insertar',], 'type'=>'POST']); ?>
        <input type="submit" value="Guardar" id="guardar" class="btn btn-primary" id="guardar">

        <?= $this->Form->end() ?>

    </div>


    <div class="excel col-md-4 columns content" style="margin-top: 30px;">
        <h3><?= __('Guardar') ?></h3>
        <?= $this->Form->create(null, ['url'=> ['action' => 'actualizaFavorActua',], 'type'=>'POST']); ?>
        <input type="submit" value="Aceptar" id="favor" class="btn btn-primary" id="guardar">
        <?= $this->Form->end() ?>

    </div>
    <br>
</div>
<br>
<button type="button" class="btn btn-info" data-toggle="modal" data-target="#exampleModal">
    Ayuda
</button>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Instrucciones</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    Boton de "Cargar registros iniciales: Sirve para importar registro de excel cuando se inicia la carga de información, es decir esta vació.

                    1. Cargar el archivo Excel y a continuación precionar el botón de "Enviar".<br><br>
                    2. Guardar Registros: Lectura de los registro dentro del excel. <br><br>
                    3. Guardar: Importa los regitros dentro del sistema
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="reg" tabindex="-1" role="dialog" aria-labelledby="reg" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Cargar Archivo de Primera Vez</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row" style="border: solid 1px #3875d7; margin: 10px; border-radius: 15px;">

                    <div class="col-md-12 columns content" style="margin-top: 30px;">
                        <?= $this->Form->create($excel, ['type' => 'file']) ?>
                        <fieldset>
                            <legend><?= __('Cargar Excel') ?></legend>
                            <?php
                            echo $this->Form->control('archivo', ['type' => 'file']);
                            echo $this->Form->input('id_user', ['type' => 'hidden', 'value' => $this->request->session()->read('Auth.User.id_user')]);
                            ?>
                        </fieldset>
                        <?= $this->Form->button(__('Submit')) ?>
                        <?= $this->Form->end() ?>

                    </div>

                    <div class="excel col-md-12 columns content" style="margin-top: 30px;">
                        <h3><?= __('Guardar Registros') ?></h3>
                        <a href="../../files/Excel/archivo/<?= $shreed->archivo ?>"   style="color: #6f42c1"><?= $shreed->archivo ?></a>
                        <?= $this->Form->create(null, ['url'=> ['action' => 'insertar',], 'type'=>'POST']); ?>
                        <input type="submit" value="Guardar" id="guardar" class="btn btn-primary" id="guardar">

                        <?= $this->Form->end() ?>

                    </div>

                    <div class="excel col-md-12 columns content" style="margin-top: 30px;">
                        <h3><?= __('Guardar') ?></h3>
                        <?= $this->Form->create(null, ['url'=> ['action' => 'actualizaFavor',], 'type'=>'POST']); ?>
                        <input type="submit" value="Aceptar" id="favor" class="btn btn-primary" id="guardar">
                        <?= $this->Form->end() ?>

                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
