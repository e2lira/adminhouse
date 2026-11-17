<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$id_user = $this->request->session()->read('Auth.User.id_user');

?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Lista de Usuarios'), ['action' => 'index']) ?></li>
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?></li>
            </div>
        </div>
        <br><br><br>

    </div>
<div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">

    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Agregar Usuario') ?></legend>
        <?php
            echo $this->Form->control('username');
            echo $this->Form->control('password');
            echo $this->Form->control('id_perfil',['type' => 'select',
            'options' => $options,
            'empty' => 'selecciona']);
            echo $this->Form->control('email');

            //echo $this->Form->control('condominio._ids', ['type'=> 'select',
            //'options' => $condomonios,
            //'empty'=> 'selecciona',
            //'multiple'=>'multiple']);

             echo $this->Form->control('id_admin', ['type'=>'hidden','value'=>$id_user]);

        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
 </div>
</div>

<script>
    $(document).ready(function () {
        console.log("Aqui ando");
        $('#condominio-ids').select2({
            cache: true,
            serverSide: true,
            placeholder:'Selecciona un condominio',
            dropdownAutoWidth: true,
            multiple: true,
            theme: "bootstrap4",
            ajax: {
                url:  '/users/getCondominios',
                dataType: 'json',
                data: function(params) {
                    var query={
                        search: params.term,
                        page: params.page || 1,
                        size: '5'
                    }
                    return query;
                }
            }
        });

        console.log('Aqui estoy');
        $('#id-perfil').select2({
            cache: true,
            serverSide: true,
            placeholder:'Selecciona un perfil',
            dropdownAutoWidth: true,
            theme: "bootstrap4",
            ajax: {
                url:  '/administrador/getPerfiles',
                dataType: 'json',
                data: function(params) {
                    var query={
                        search: params.term,
                        page: params.page || 1,
                        size: '5'
                    }
                    return query;
                }
            }
        });
    });
</script>

