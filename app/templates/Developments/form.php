<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Development $development
 * @var string $title
 * @var string $url
 * @var array<string, string> $types
 */
$this->assign('title', $title);
?>
<p><a href="<?= h($this->Url->build('/developments')) ?>">&larr; Desarrollos</a></p>
<h1><?= h($title) ?></h1>

<?= $this->Form->create($development, ['url' => $url]) ?>
<?= $this->Form->control('type', [
    'label' => 'Tipo',
    'type' => 'select',
    'options' => $types,
    'empty' => 'Elige un tipo',
]) ?>
<?= $this->Form->control('name', ['label' => 'Nombre', 'maxlength' => 150]) ?>
<?= $this->Form->control('section_label', ['label' => 'Nombre de la sección (por ejemplo Calle o Torre)', 'maxlength' => 150]) ?>
<?= $this->Form->control('number_label', ['label' => 'Nombre del número (por ejemplo Número o Departamento)', 'maxlength' => 150]) ?>
<?= $this->Form->control('timezone', ['label' => 'Zona horaria (por ejemplo America/Mexico_City)', 'type' => 'text']) ?>

<h2>Datos de contacto y bancarios (opcionales)</h2>
<?= $this->Form->control('address', ['label' => 'Dirección', 'maxlength' => 255]) ?>
<?= $this->Form->control('phone', ['label' => 'Teléfono', 'maxlength' => 32]) ?>
<?= $this->Form->control('rfc', ['label' => 'RFC', 'maxlength' => 32]) ?>
<?= $this->Form->control('bank_name', ['label' => 'Banco', 'maxlength' => 255]) ?>
<?= $this->Form->control('bank_account', ['label' => 'Cuenta bancaria', 'maxlength' => 32, 'autocomplete' => 'off']) ?>

<?= $this->Form->button('Guardar') ?>
<?= $this->Form->end() ?>
