<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Development $development
 * @var \App\Model\Entity\Person $person
 * @var string $title
 * @var string $url
 */
$this->assign('title', $title);
?>
<p><a href="<?= h($this->Url->build('/developments/' . (int)$development->id . '/people')) ?>">&larr; Personas</a></p>
<h1><?= h($title) ?></h1>

<?= $this->Form->create($person, ['url' => $url]) ?>
<?= $this->Form->control('name', [
    'label' => 'Nombre',
    'type' => 'text',
    'maxlength' => 150,
    'required' => false,
]) ?>
<?= $this->Form->control('email', [
    'label' => 'Correo electrónico (opcional)',
    'type' => 'text',
    'inputmode' => 'email',
    'maxlength' => 254,
    'required' => false,
]) ?>
<?= $this->Form->control('phone', [
    'label' => 'Teléfono (opcional)',
    'type' => 'text',
    'inputmode' => 'tel',
    'maxlength' => 30,
]) ?>
<?= $this->Form->control('rfc', [
    'label' => 'RFC (opcional)',
    'type' => 'text',
    'maxlength' => 13,
]) ?>

<?= $this->Form->button('Guardar') ?>
<?= $this->Form->end() ?>
