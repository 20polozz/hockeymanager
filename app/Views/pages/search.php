<?php
/** @var string $q @var array $players @var array $teams */
?>
<?php if (mb_strlen($q) < 2): ?>
  <section class="panel"><?= component('empty_state', ['icon' => 'search', 'text' => 'Escriu com a mínim 2 lletres al cercador.']) ?></section>
<?php else: ?>
  <p class="muted">Resultats per a «<?= e($q) ?>»</p>
  <div class="dash-grid">
    <section class="panel">
      <?= component('panel_head', ['title' => 'Jugadors']) ?>
      <?php if ($players): ?>
        <ul class="plain-list">
          <?php foreach ($players as $p): ?>
            <li><a class="person" href="<?= e(url('/jugadors/' . (int)$p['id'])) ?>"><span class="avatar avatar-sm"><?= e(initials($p['first_name'] . ' ' . $p['last_name'])) ?></span><span><?= e($p['first_name'] . ' ' . $p['last_name']) ?> <span class="muted small">· <?= e($p['team_name']) ?></span></span></a></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <?= component('empty_state', ['icon' => 'users', 'text' => 'Cap jugador trobat.']) ?>
      <?php endif; ?>
    </section>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Equips']) ?>
      <?php if ($teams): ?>
        <ul class="plain-list">
          <?php foreach ($teams as $t): ?>
            <li><span class="person"><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><?= e($t['name']) ?> <span class="muted small">· <?= e((string)$t['category']) ?></span></span></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <?= component('empty_state', ['icon' => 'shield', 'text' => 'Cap equip trobat.']) ?>
      <?php endif; ?>
    </section>
  </div>
<?php endif; ?>
