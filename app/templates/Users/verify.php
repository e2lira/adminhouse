<?php
/**
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Verificación en dos pasos');
?>
<h1>Verificación en dos pasos</h1>
<p>Ingresa el código de 6 dígitos de tu aplicación de autenticación. Si no tienes tu dispositivo, usa uno de tus códigos de respaldo.</p>
<?= $this->Form->create(null) ?>
<?= $this->Form->control('code', [
    'label' => 'Código',
    'required' => true,
    'autofocus' => true,
    'autocomplete' => 'one-time-code',
    'inputmode' => 'text',
    'value' => '',
]) ?>
<?= $this->Form->button('Verificar') ?>
<?= $this->Form->end() ?>
<p><?= $this->Html->link('Volver a iniciar sesión', ['action' => 'login']) ?></p>
