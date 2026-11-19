<?php
/** @var array $teams @var int $team @var array $today @var array $upcoming @var array $past */
$row = function (array $t): string {
    $marked = (int)$t['marked'];
    ob_start(); ?>
    <li>
      <a class="session-row" href="<?= e(url('/assistencia/' . (int)$t['id'])) ?>">
        <span class="date-chip">
          <span class="date-chip-month"><?= e(fdate($t['starts_at'], 'monthshort')) ?></span>
          <span class="date-chip-day"><?= e(fdate($t['starts_at'], 'daynum')) ?></span>
        </span>
        <span class="event-body">
          <span class="event-title"><?= e($t['title']) ?></span>
          <span class="event-meta"><span class="team-dot" style="--team: <?= e($t['type'] === 'gimnas' ? '#7c3aed' : $t['color']) ?>"></span><?= $t['type'] === 'gimnas' ? 'Gimnàs · ' : '' ?><?= e($t['team_name']) ?> · <?= e(fdate($t['starts_at'], 'short')) ?> · <?= e(fdate($t['starts_at'], 'time')) ?></span>
        </span>
        <?php if ($marked > 0): ?>
          <span class="session-status ok"><?= icon('check', 'icon icon-xs') ?> <?= (int)$t['attended'] ?>/<?= $marked ?></span>
        <?php else: ?>
          <span class="session-status pending">Pendent</span>
        <?php endif; ?>
        <?= icon('chevron-right', 'icon icon-sm muted') ?>
      </a>
    </li>
    <?php return (string)ob_get_clean();
};
?>
<?= App\Core\View::partial('pages/attendance/_tabs', ['tab' => 'list', 'team' => $team]) ?>
<?= component('team_chips', ['teams' => $teams, 'active' => $team, 'path' => '/assistencia']) ?>

<section class="today-block" aria-labelledby="today-title">
  <h2 id="today-title" class="section-title">Avui <span class="muted small"><?= e(ucfirst(fdate(date('Y-m-d'), 'day'))) ?></span></h2>
  <?php if ($today): ?>
    <div class="today-grid">
      <?php foreach ($today as $t):
          $marked = (int)$t['marked'];
          $now = time(); $start = strtotime((string)$t['starts_at']); $end = $t['ends_at'] ? strtotime((string)$t['ends_at']) : $start + 3600;
          $state = $now < $start ? 'Comença a les ' . fdate($t['starts_at'], 'time') : ($now <= $end ? 'Ara en curs' : 'Acabat'); ?>
        <article class="today-card<?= $marked > 0 ? ' is-done' : '' ?><?= $now >= $start && $now <= $end ? ' is-live' : '' ?>">
          <div class="today-time">
            <strong><?= e(fdate($t['starts_at'], 'time')) ?></strong>
            <?php if ($t['ends_at']): ?><span><?= e(fdate($t['ends_at'], 'time')) ?></span><?php endif; ?>
          </div>
          <div class="today-body">
            <h3><?= e($t['title']) ?></h3>
            <p class="muted small"><span class="team-dot" style="--team: <?= e($t['type'] === 'gimnas' ? '#7c3aed' : $t['color']) ?>"></span><?= $t['type'] === 'gimnas' ? 'Gimnàs · ' : '' ?><?= e($t['team_name']) ?><?= $t['location'] ? ' · ' . e($t['location']) : '' ?></p>
            <p class="today-state small"><?= $marked > 0 ? icon('check', 'icon icon-xs') . ' Llista passada · ' . (int)$t['attended'] . '/' . $marked . ' presents' : e($state) ?></p>
          </div>
          <a class="btn <?= $marked > 0 ? '' : 'btn-primary' ?> today-btn" href="<?= e(url('/assistencia/' . (int)$t['id'])) ?>"><?= icon('checklist', 'icon icon-sm') ?> <?= $marked > 0 ? 'Revisa la llista' : 'Passa llista' ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="panel today-empty">
      <?= icon('calendar', 'icon') ?>
      <span>Avui no hi ha cap entrenament.<?php if ($upcoming): ?> El pròxim: <strong><?= e($upcoming[0]['title']) ?></strong>, <?= e(fdate($upcoming[0]['starts_at'], 'short')) ?> a les <?= e(fdate($upcoming[0]['starts_at'], 'time')) ?>.<?php endif; ?></span>
    </div>
  <?php endif; ?>
</section>

<div class="dash-grid">
  <section class="panel">
    <?= component('panel_head', ['title' => 'Entrenaments recents', 'href' => url('/calendari/nou', ['tipus' => 'entrenament'] + ($team ? ['equip' => $team] : [])), 'linkText' => 'Nou entrenament']) ?>
    <?php if ($past): ?>
      <ul class="session-list"><?php foreach ($past as $t) { echo $row($t); } ?></ul>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'checklist', 'text' => 'No hi ha entrenaments les últimes setmanes. Crea\'ls al calendari (pots fer que es repeteixin cada setmana).']) ?>
    <?php endif; ?>
  </section>
  <section class="panel">
    <?= component('panel_head', ['title' => 'Pròxims entrenaments', 'href' => url('/calendari', ['vista' => 'setmana'] + ($team ? ['equip' => $team] : [])), 'linkText' => 'Calendari']) ?>
    <?php if ($upcoming): ?>
      <ul class="session-list"><?php foreach ($upcoming as $t) { echo $row($t); } ?></ul>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'calendar', 'text' => 'No hi ha entrenaments programats els pròxims dies.']) ?>
    <?php endif; ?>
  </section>
</div>
