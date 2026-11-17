<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Excel $excel
 */
?>
<div class="row" style="border: solid 1px #3875d7; margin: 10px; border-radius: 15px;">
    <div class="col-md-6 columns content" style="margin-top: 30px;">
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

    <div class="excel col-md-6 columns content" style="margin-top: 30px;">
        <h3><?= __('Guardar Registros') ?></h3>
        <a href="../../files/Excel/archivo/<?= $shreed->archivo ?>"   style="color: #6f42c1"><?= $shreed->archivo ?></a>
        <?= $this->Form->create(null, ['url'=> ['action' => 'insertar',], 'type'=>'POST']); ?>
        <input type="submit" value="Guardar" id="buscar" class="btn btn-primary">

        <?= $this->Form->end() ?>

    </div>

</div>
