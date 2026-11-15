<?php
/** @var array $teams @var int $team @var array $upcoming @var array $past */
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
