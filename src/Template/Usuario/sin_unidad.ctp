<?php
/**
 * Shown on the resident landing page when the account has no unit assigned.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kualitet</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f2f4f6; color: #2c3e50; }
        .aviso { max-width: 420px; margin: 80px auto; padding: 32px 28px; background: #ffffff;
                 border-radius: 4px; box-shadow: 0 2px 2px rgba(0, 0, 0, 0.15); text-align: center; }
        .aviso h1 { font-size: 20px; margin: 0 0 12px; }
        .aviso p { font-size: 15px; line-height: 1.5; margin: 0 0 20px; }
        .aviso a { color: #2a6496; }
    </style>
</head>
<body>
    <div class="aviso">
        <h1>Sin unidad asignada</h1>
        <p>Tu usuario no tiene una unidad asignada. Contacta a la administración de tu condominio para que la asocie a tu cuenta.</p>
        <?= $this->Html->link('Cerrar sesión', ['controller' => 'Users', 'action' => 'logout']) ?>
    </div>
</body>
</html>
