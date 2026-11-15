<?php
/** @var array $teams @var int $team @var string $period @var array $players @var array $byTeam @var array $monthly @var array $mostAbsent */
$months = ['gen', 'febr', 'març', 'abr', 'maig', 'juny', 'jul', 'ag', 'set', 'oct', 'nov', 'des'];
$q = $team > 0 ? ['equip' => $team] : [];
?>
<?= App\Core\View::partial('pages/attendance/_tabs', ['tab' => 'stats', 'team' => $team]) ?>
<?= component('team_chips', ['teams' => $teams, 'active' => $team, 'path' => '/assistencia/estadistiques', 'query' => ['periode' => $period]]) ?>

<div class="page-head">
  <div class="segmented segmented-links">
    <?php foreach (['30' => 'Últims 30 dies', '90' => 'Últims 90 dies', 'temporada' => 'Temporada'] as $k => $label): ?>
      <a href="<?= e(url('/assistencia/estadistiques', $q + ['periode' => $k])) ?>" class="<?= $period === $k ? 'is-active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="dash-grid">
  <section class="panel">
    <?= component('panel_head', ['title' => 'Per equip']) ?>
    <?php if (array_filter($byTeam, function ($t) { return $t['percent'] !== null; })): ?>
      <ul class="bar-list">
        <?php foreach ($byTeam as $t): ?>
          <li>
            <span class="bar-label"><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><?= e($t['name']) ?></span>
            <span class="bar-track"><span class="bar-fill<?= $t['percent'] !== null && $t['percent'] < 75 ? ' bar-warn' : '' ?>" style="width: <?= (int)$t['percent'] ?>%"></span></span>
            <span class="bar-value"><?= $t['percent'] === null ? '—' : (int)$t['percent'] . '%' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'chart', 'text' => 'Encara no hi ha llistes en aquest període.']) ?>
    <?php endif; ?>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Evolució mensual']) ?>
    <div class="col-chart" role="img" aria-label="Percentatge d'assistència dels últims 6 mesos">
      <?php foreach ($monthly as $m): ?>
        <div class="col">
          <span class="col-value"><?= $m['percent'] === null ? '—' : $m['percent'] . '%' ?></span>
          <span class="col-bar"><span style="height: <?= (int)$m['percent'] ?>%"<?= $m['percent'] !== null && $m['percent'] < 75 ? ' class="warn"' : '' ?>></span></span>
          <span class="col-label"><?= e($months[(int)substr($m['month'], 5, 2) - 1]) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Més absències']) ?>
    <?php if ($mostAbsent): ?>
      <ul class="plain-list">
        <?php foreach ($mostAbsent as $p): ?>
          <li class="rank-row">
            <a class="person" href="<?= e(url('/jugadors/' . (int)$p['id'])) ?>"><span class="avatar avatar-sm"><?= e(initials($p['first_name'] . ' ' . $p['last_name'])) ?></span><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
            <span class="small"><?php if ((int)$p['absent'] > 0): ?><span class="pill pill-red"><?= (int)$p['absent'] ?> <?= (int)$p['absent'] === 1 ? 'absència' : 'absències' ?></span><?php endif; ?> <?php if ((int)$p['justificat'] > 0): ?><span class="pill pill-orange"><?= (int)$p['justificat'] ?> just.</span><?php endif; ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'check', 'text' => 'Cap absència en aquest període.']) ?>
    <?php endif; ?>
  </section>
</div>

<section class="panel panel-flush stats-table-panel">
  <div class="panel-head panel-pad"><h2 class="panel-title">Per jugador/a</h2></div>
  <?php if ($players): ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th scope="col">Jugador/a</th><th scope="col" class="num">Sessions</th><th scope="col" class="num">Presents</th><th scope="col" class="num">Retards</th><th scope="col" class="num">Justif.</th><th scope="col" class="num">Absències</th><th scope="col">Assistència</th></tr></thead>
        <tbody>
          <?php foreach ($players as $p): ?>
            <tr>
              <td><a class="person" href="<?= e(url('/jugadors/' . (int)$p['id'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a></td>
              <td class="num"><?= (int)$p['total'] ?></td>
              <td class="num"><?= (int)$p['present'] ?></td>
              <td class="num"><?= (int)$p['retard'] ?></td>
              <td class="num"><?= (int)$p['justificat'] ?></td>
              <td class="num"><?= (int)$p['absent'] ?></td>
              <td><span class="mini-bar"><span style="width: <?= (int)$p['percent'] ?>%"<?= $p['percent'] !== null && $p['percent'] < 75 ? ' class="warn"' : '' ?>></span></span> <strong><?= $p['percent'] === null ? '—' : $p['percent'] . '%' ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <?= component('empty_state', ['icon' => 'users', 'text' => 'Encara no hi ha dades d\'assistència en aquest període.']) ?>
  <?php endif; ?>
</section>
