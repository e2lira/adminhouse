<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Development $development
 * @var \App\Model\Entity\Unit $unit
 * @var bool $canManage
 * @var array<string, string> $types
 */
$this->assign('title', 'Unidad');
$base = '/developments/' . (int)$development->id . '/units';
?>
<p><a href="<?= h($this->Url->build($base)) ?>">&larr; Unidades</a></p>
<h1><?= h($development->number_label) ?> <?= h($unit->number) ?></h1>
<dl>
    <dt><?= h($development->section_label) ?></dt>
    <dd><?= h($unit->section?->name ?? 'Sin asignar') ?></dd>
    <dt><?= h($development->number_label) ?></dt>
    <dd><?= h($unit->number) ?></dd>
    <dt>Tipo</dt>
    <dd><?= h($types[$unit->type] ?? $unit->type) ?></dd>
    <dt>Superficie del lote (m²)</dt>
    <dd><?= h($unit->lot_area_m2 ?? 'Sin dato') ?></dd>
    <dt>Indiviso (%)</dt>
    <dd><?= h($unit->indiviso_pct ?? 'Sin dato') ?></dd>
    <dt>Estado</dt>
    <dd><?= $unit->is_active ? 'Activa' : 'Inactiva' ?></dd>
</dl>
<?php if ($canManage) : ?>
<p><a href="<?= h($this->Url->build($base . '/' . (int)$unit->id . '/edit')) ?>">Editar</a></p>
<?php endif; ?>
