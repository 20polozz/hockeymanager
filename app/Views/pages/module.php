<?php
/**
 * Pàgina d'un mòdul encara en desenvolupament.
 * @var string $pageTitle @var string $moduleIcon @var int $phase @var string $intro @var array $features
 */
?>
<section class="panel module-hero">
  <span class="tile tile-xl tone-navy"><?= icon($moduleIcon) ?></span>
  <div>
    <span class="pill pill-orange"><?= icon('wrench', 'icon icon-xs') ?> En desenvolupament · fase <?= (int)$phase ?></span>
    <h2 class="module-title"><?= e($pageTitle) ?></h2>
    <p class="muted"><?= e($intro) ?></p>
  </div>
</section>

<section class="panel">
  <?= component('panel_head', ['title' => 'Què inclourà aquest mòdul']) ?>
  <ul class="check-list">
    <?php foreach ($features as $f): ?>
      <li><?= icon('check', 'icon icon-sm') ?> <?= e($f) ?></li>
    <?php endforeach; ?>
  </ul>
  <p class="muted small">La base de dades ja té preparades les taules que farà servir. Mentrestant, pots consultar-ne el resum al <a href="<?= e(url('/')) ?>">Dashboard</a>.</p>
</section>
