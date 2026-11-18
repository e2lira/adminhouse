<?php
/**
 * @var \App\View\AppView $this
 * @var list<string> $codes
 * @var string $continueUrl
 */
$this->assign('title', 'Códigos de respaldo');
?>
<h1>Guarda tus códigos de respaldo</h1>
<p>La verificación en dos pasos quedó activada y ya iniciaste sesión.</p>
<p><strong>Esta es la única vez que verás estos códigos.</strong> Cada uno sirve una sola vez si pierdes tu dispositivo. Guárdalos en un lugar seguro, fuera de este equipo.</p>
<ul class="backup-codes">
    <?php foreach ($codes as $code) : ?>
    <li><code><?= h($code) ?></code></li>
    <?php endforeach; ?>
</ul>
<p><?= $this->Html->link('Ya los guardé, continuar', $continueUrl, ['class' => 'button']) ?></p>
