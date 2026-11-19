<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Development $development
 * @var \App\Model\Entity\Unit $unit
 * @var string $title
 * @var string $url
 * @var array<int, string> $sections
 * @var array<string, string> $types
 */
$this->assign('title', $title);
?>
<p><a href="<?= h($this->Url->build('/developments/' . (int)$development->id . '/units')) ?>">&larr; Unidades</a></p>
<h1><?= h($title) ?></h1>

<?= $this->Form->create($unit, ['url' => $url]) ?>
<?= $this->Form->control('section_id', [
    'label' => $development->section_label,
    'type' => 'select',
    'options' => $sections,
    'empty' => 'Sin ' . mb_strtolower($development->section_label),
]) ?>
<?= $this->Form->control('number', [
    'label' => $development->number_label,
    'type' => 'text',
    'maxlength' => 50,
    'required' => false,
]) ?>
<?= $this->Form->control('type', [
    'label' => 'Tipo',
    'type' => 'select',
    'options' => $types,
    'empty' => 'Elige un tipo',
    'required' => false,
]) ?>
<?= $this->Form->control('lot_area_m2', [
    'label' => 'Superficie del lote (m², opcional)',
    'type' => 'text',
    'inputmode' => 'decimal',
    'maxlength' => 16,
]) ?>
<?= $this->Form->control('indiviso_pct', [
    'label' => 'Indiviso (%, opcional)',
    'type' => 'text',
    'inputmode' => 'decimal',
    'maxlength' => 10,
]) ?>

<?= $this->Form->button('Guardar') ?>
<?= $this->Form->end() ?>
