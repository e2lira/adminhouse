<?php
/**
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Iniciar sesión');
?>
<h1>Iniciar sesión</h1>
<?= $this->Form->create(null) ?>
<?= $this->Form->control('username', [
    'label' => 'Usuario',
    'required' => true,
    'autofocus' => true,
    'autocomplete' => 'username',
]) ?>
<?= $this->Form->control('password', [
    'label' => 'Contraseña',
    'type' => 'password',
    'required' => true,
    'autocomplete' => 'current-password',
    'value' => '',
]) ?>
<?= $this->Form->button('Entrar') ?>
<?= $this->Form->end() ?>
