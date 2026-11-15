<?php
/** @var array $teams @var int $team @var array $sessions @var bool $canManage */
?>
<?= component('team_chips', ['teams' => $teams, 'active' => $team, 'path' => '/entrenaments']) ?>
<div class="page-head">
  <p class="muted"><?= count($sessions) ?> sessions planificades</p>
  <div class="head-actions">
    <a class="btn" href="<?= e(url('/exercicis')) ?>"><?= icon('cone', 'icon icon-sm') ?> Biblioteca d'exercicis</a>
    <?php if ($canManage): ?>
      <a class="btn btn-primary" href="<?= e(url('/entrenaments/nou', $team ? ['equip' => $team] : [])) ?>"><?= icon('plus', 'icon icon-sm') ?> Nova sessió</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$sessions): ?>
  <section class="panel"><?= component('empty_state', ['icon' => 'dumbbell', 'text' => 'Encara no hi ha cap sessió. Crea\'n una i combina-hi exercicis de la biblioteca.']) ?></section>
<?php else: ?>
  <div class="ex-grid">
    <?php foreach ($sessions as $s): ?>
      <a class="session-card" href="<?= e(url('/entrenaments/' . (int)$s['id'])) ?>">
        <span class="session-card-top">
          <?php if ($s['team_name']): ?><span class="pill pill-muted"><span class="team-dot" style="--team: <?= e($s['color']) ?>"></span><?= e($s['team_name']) ?></span><?php else: ?><span class="pill pill-muted">General</span><?php endif; ?>
          <?php if ($s['event_at']): ?><span class="small muted"><?= icon('calendar', 'icon icon-xs') ?> <?= e(fdate($s['event_at'], 'short')) ?></span><?php endif; ?>
        </span>
        <h2 class="ex-name"><?= e($s['title']) ?></h2>
        <span class="ex-meta">
          <span><?= icon('cone', 'icon icon-xs') ?> <?= (int)$s['exercises'] ?> exercicis</span>
          <span><?= icon('clock', 'icon icon-xs') ?> <?= (int)$s['total_minutes'] ?> min</span>
          <?php if ($s['author']): ?><span><?= icon('user', 'icon icon-xs') ?> <?= e($s['author']) ?></span><?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
