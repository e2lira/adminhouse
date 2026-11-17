<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="row">
    <div class="col-md-2">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Editar Usuario'), ['action' => 'edit', $user['id_user']]) ?> </li>
                    <li><?= $this->Form->postLink(__('Borrar Usuario'), ['action' => 'delete', $user['id_user']], ['confirm' => __('Estas seguro de querer borrar  el usuario # {0}?', $user['id_user'])]) ?> </li>
                    <li><?= $this->Html->link(__('Lista de Usuarios'), ['action' => 'index']) ?> </li>
                    <li><?= $this->Html->link(__('Nuevo Usuario'), ['action' => 'add']) ?> </li>
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['controller' => 'Condominio', 'action' => 'index']) ?> </li>
                    <li><?= $this->Html->link(__('Nuevo Condominio'), ['controller' => 'Condominio', 'action' => 'add']) ?> </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-10">
        <div class="azul">
            <h2><i class="fa fa-user-circle"></i> <?= h($user['username'])?></h2>
            <table class="table  table-condensed table-responsive">
                <th scope="row"><?= __('Password') ?></th>
                <td><?= h($user['password']) ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= __('Email') ?></th>
                    <td><?= h($user['email']) ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= __('Id Usuario') ?></th>
                    <td><?= h($user['id_user']) ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= __('Perfil') ?></th>
                    <td><?= h($user['perfil']) ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= __('Creado') ?></th>
                    <td><?= h($user['created']) ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= __('Modificado') ?></th>
                    <td><?= h($user['modified']) ?></td>
                </tr>
            </table>
        </div>

        <div class="related">
            <h4><?= __('Condominios Asignados') ?></h4>
            <?php if (!empty($condominios)): ?>
            <ul>
                <?php foreach($condominios as $condominio){
        echo "<li>Administra: ".$condominio->condominio."</li>";
                }   ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
