<?php
/**
 * @var \App\View\AppView $this
 * @var string $qrSvg
 * @var string $secret
 */
$this->assign('title', 'Configura la verificación en dos pasos');
?>
<h1>Configura la verificación en dos pasos</h1>
<p>Tu cuenta requiere un segundo factor. Sigue estos pasos una sola vez:</p>
<ol>
    <li>Abre una aplicación de autenticación (por ejemplo, Google Authenticator, Microsoft Authenticator o Aegis).</li>
    <li>Escanea este código QR, o captura la clave manualmente.</li>
    <li>Escribe el código de 6 dígitos que muestra la aplicación.</li>
</ol>
<div class="totp-qr"><?= $qrSvg /* markup generated locally from a fixed template; contains no user input */ ?></div>
<p>Clave manual: <code><?= h($secret) ?></code></p>
<?= $this->Form->create(null) ?>
<?= $this->Form->control('code', [
    'label' => 'Código de 6 dígitos',
    'required' => true,
    'autofocus' => true,
    'autocomplete' => 'one-time-code',
    'inputmode' => 'numeric',
    'value' => '',
]) ?>
<?= $this->Form->button('Confirmar') ?>
<?= $this->Form->end() ?>
<p><?= $this->Html->link('Volver a iniciar sesión', ['action' => 'login']) ?></p>
