<?php
/**
 * @var string $calView @var DateTime $date @var DateTime $from @var DateTime $to
 * @var DateTime $prev @var DateTime $next @var array $byDay @var int $count
 * @var array $teams @var int $team @var bool $canManage
 */
$months = ['gener', 'febrer', 'març', 'abril', 'maig', 'juny', 'juliol', 'agost', 'setembre', 'octubre', 'novembre', 'desembre'];
$dows = ['Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds', 'Dg'];
$base = $team > 0 ? ['equip' => $team] : [];
$link = function (string $v, DateTime $d) use ($base): string {
    return url('/calendari', $base + ['vista' => $v, 'data' => $d->format('Y-m-d')]);
};
$today = date('Y-m-d');
$kinds = ['entrenament' => 'Entrenament', 'partit' => 'Partit', 'especial' => 'Especial', 'gimnas' => 'Gimnàs'];
$itemUrl = function (array $it): string {
    return $it['kind'] === 'partit' ? url('/partits/' . (int)$it['id']) : url('/calendari/' . (int)$it['id']);
};
switch ($calView) {
    case 'dia':
        $title = ucfirst(fdate($date->format('Y-m-d'), 'day')) . ' ' . $date->format('Y');
        break;
    case 'setmana':
        $end = (clone $to)->modify('-1 day');
        $title = $from->format('j') . ' ' . ($from->format('n') !== $end->format('n') ? $months[(int)$from->format('n') - 1] . ' ' : '') . '– ' . $end->format('j') . ' ' . $months[(int)$end->format('n') - 1] . ' ' . $end->format('Y');
        break;
    default:
        $title = ucfirst($months[(int)$date->format('n') - 1]) . ' ' . $date->format('Y');
}
?>
<?= component('team_chips', ['teams' => $teams, 'active' => $team, 'path' => '/calendari', 'query' => ['vista' => $calView, 'data' => $date->format('Y-m-d')]]) ?>

<div class="cal-toolbar">
  <div class="cal-nav">
    <a class="icon-btn bordered" href="<?= e($link($calView, $prev)) ?>" aria-label="Anterior"><?= icon('chevron-right', 'icon flip') ?></a>
    <a class="btn btn-sm" href="<?= e($link($calView, new DateTime('today'))) ?>">Avui</a>
    <a class="icon-btn bordered" href="<?= e($link($calView, $next)) ?>" aria-label="Següent"><?= icon('chevron-right') ?></a>
    <h2 class="cal-title"><?= e($title) ?></h2>
  </div>
  <div class="cal-actions">
    <div class="segmented segmented-links" role="tablist" aria-label="Vista">
      <?php foreach (['mes' => 'Mes', 'setmana' => 'Setmana', 'dia' => 'Dia'] as $v => $label): ?>
        <a href="<?= e($link($v, $date)) ?>" class="<?= $calView === $v ? 'is-active' : '' ?>"<?= $calView === $v ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($canManage): ?>
      <a class="btn btn-primary" href="<?= e(url('/calendari/nou', $base + ['data' => $date->format('Y-m-d')])) ?>"><?= icon('plus', 'icon icon-sm') ?> Nou</a>
    <?php endif; ?>
  </div>
</div>

<div class="cal-legend">
  <span><i class="dot dot-blue"></i>Entrenament</span>
  <span><i class="dot dot-green"></i>Partit</span>
  <span><i class="dot dot-violet"></i>Gimnàs</span>
  <span><i class="dot dot-orange"></i>Esdeveniment especial</span>
</div>

<?php if ($calView === 'mes'): ?>
  <section class="panel panel-flush">
    <div class="cal-grid" role="grid" aria-label="<?= e($title) ?>">
      <?php foreach ($dows as $dw): ?><div class="cal-dow" role="columnheader"><?= $dw ?></div><?php endforeach; ?>
      <?php for ($d = clone $from; $d < $to; $d->modify('+1 day')):
          $key = $d->format('Y-m-d');
          $items = $byDay[$key] ?? [];
          $out = $d->format('n') !== $date->format('n'); ?>
        <div class="cal-cell<?= $out ? ' is-out' : '' ?><?= $key === $today ? ' is-today' : '' ?>" role="gridcell">
          <a class="cal-daynum" href="<?= e($link('dia', $d)) ?>" aria-label="<?= e(fdate($key, 'day')) ?>" data-day="<?= e($key) ?>"><?= $d->format('j') ?></a>
          <?php foreach (array_slice($items, 0, 3) as $it): ?>
            <a class="cal-item kind-<?= e($it['kind']) ?>" href="<?= e($itemUrl($it)) ?>" title="<?= e($it['team_name'] . ' · ' . $it['title']) ?>">
              <span class="cal-item-time"><?= e(fdate($it['starts_at'], 'time')) ?></span>
              <span class="cal-item-title"><?= e($team > 0 || $it['kind'] !== 'entrenament' || $it['team_id'] === null ? $it['title'] : $it['team_name']) ?></span>
            </a>
          <?php endforeach; ?>
          <?php if (count($items) > 3): ?>
            <a class="cal-more" href="<?= e($link('dia', $d)) ?>">+<?= count($items) - 3 ?> més</a>
          <?php endif; ?>
          <?php if ($items): ?>
            <span class="cal-dots" aria-hidden="true"><?php foreach (array_slice($items, 0, 4) as $it): ?><i class="kind-<?= e($it['kind']) ?>"></i><?php endforeach; ?></span>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  </section>

  <?php // Al mòbil, sota la graella: llista dels dies amb activitat (des d'avui si és el mes actual)
  $listFrom = $date->format('Y-m') === date('Y-m') ? $today : $date->format('Y-m-01'); ?>
  <div class="agenda month-agenda">
    <?php foreach ($byDay as $key => $items):
        if ($key < $listFrom || substr($key, 0, 7) !== $date->format('Y-m')) { continue; } ?>
      <section class="agenda-day<?= $key === $today ? ' is-today' : '' ?>" id="dia-<?= e($key) ?>">
        <h3 class="agenda-date"><?= e(ucfirst(fdate($key, 'day'))) ?></h3>
        <ul class="agenda-list">
          <?php foreach ($items as $it): ?>
            <li><a class="agenda-item kind-<?= e($it['kind']) ?>" href="<?= e($itemUrl($it)) ?>">
              <span class="agenda-time"><?= e(fdate($it['starts_at'], 'time')) ?><?php if ($it['ends_at']): ?><small><?= e(fdate($it['ends_at'], 'time')) ?></small><?php endif; ?></span>
              <span class="agenda-body"><strong><?= e($it['title']) ?></strong><span class="muted small"><?= $it['kind'] === 'gimnas' ? 'Gimnàs · ' : '' ?><?= e($it['team_name']) ?></span></span>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
<?php elseif ($calView === 'setmana'):
  // ---- Setmana: graella horària (ordinador) + franja de dies i llista (mòbil) ----
  $days = [];
  for ($d = clone $from; $d < $to; $d->modify('+1 day')) { $days[] = $d->format('Y-m-d'); }
  $toMin = static function (string $dt): int { return (int)substr($dt, 11, 2) * 60 + (int)substr($dt, 14, 2); };
  $startH = 24; $endH = 0; $blocks = [];
  foreach ($days as $key) {
      $evs = [];
      foreach ($byDay[$key] ?? [] as $it) {
          $s0 = $toMin((string)$it['starts_at']);
          $e0 = $it['ends_at'] && substr((string)$it['ends_at'], 0, 10) === $key ? $toMin((string)$it['ends_at']) : $s0 + ($it['kind'] === 'partit' ? 90 : 60);
          $e0 = max($e0, $s0 + 30);
          $startH = min($startH, intdiv($s0, 60)); $endH = max($endH, (int)ceil($e0 / 60));
          $evs[] = ['it' => $it, 's' => $s0, 'e' => $e0];
      }
      // Carrils per als que es solapen
      usort($evs, static function ($a, $b) { return [$a['s'], $a['e']] <=> [$b['s'], $b['e']]; });
      $lanesEnd = []; $group = []; $groupEnd = -1; $out = [];
      $flush = static function () use (&$group, &$out, &$lanesEnd) {
          $n = max(1, count($lanesEnd));
          foreach ($group as $g) { $g['n'] = $n; $out[] = $g; }
          $group = []; $lanesEnd = [];
      };
      foreach ($evs as $ev) {
          if ($ev['s'] >= $groupEnd && $group) { $flush(); }
          $lane = null;
          foreach ($lanesEnd as $i => $end) { if ($end <= $ev['s']) { $lane = $i; break; } }
          if ($lane === null) { $lane = count($lanesEnd); }
          $lanesEnd[$lane] = $ev['e'];
          $ev['lane'] = $lane; $group[] = $ev; $groupEnd = max($groupEnd, $ev['e']);
      }
      if ($group) { $flush(); }
      $blocks[$key] = $out;
  }
  if ($startH > $endH) { $startH = 17; $endH = 22; }
  if ($endH - $startH < 5) { $startH = max(0, min($startH, $endH - 5)); $endH = min(24, $startH + 5); }
  $hourPx = 60; $gridH = ($endH - $startH) * $hourPx;
  $kindTone = ['partit' => 'green', 'especial' => 'orange', 'gimnas' => 'violet', 'entrenament' => 'blue'];
?>
  <!-- Mòbil: franja de dies -->
  <nav class="week-strip" aria-label="Dies de la setmana">
    <?php foreach ($days as $i => $key): $n = count($byDay[$key] ?? []); ?>
      <a href="<?= $n ? '#dia-' . $key : e($link('dia', new DateTime($key))) ?>" class="week-strip-day<?= $key === $today ? ' is-today' : '' ?><?= $n ? '' : ' is-empty' ?>">
        <span class="week-strip-dow"><?= $dows[$i] ?></span>
        <span class="week-strip-num"><?= (int)substr($key, 8, 2) ?></span>
        <span class="week-strip-dots"><?php foreach (array_slice($byDay[$key] ?? [], 0, 3) as $it): ?><i class="tone-dot-<?= $kindTone[$it['kind']] ?? 'blue' ?>"></i><?php endforeach; ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Mòbil: llista compacta (només dies amb activitat) -->
  <div class="week-list">
    <?php if ($count === 0): ?>
      <section class="panel"><?= component('empty_state', ['icon' => 'calendar', 'text' => 'No hi ha res programat aquesta setmana.']) ?></section>
    <?php endif; ?>
    <?php foreach ($days as $key): if (empty($byDay[$key])) { continue; } ?>
      <section class="week-list-day<?= $key === $today ? ' is-today' : '' ?>" id="dia-<?= $key ?>">
        <h3 class="week-list-date"><?= e(ucfirst(fdate($key, 'day'))) ?><?= $key === $today ? ' <span class="pill pill-blue">Avui</span>' : '' ?></h3>
        <ul class="week-list-items">
          <?php foreach ($byDay[$key] as $it): ?>
            <li><a class="wl-item tone-<?= $kindTone[$it['kind']] ?? 'blue' ?>" href="<?= e($itemUrl($it)) ?>">
              <span class="wl-time"><?= e(fdate($it['starts_at'], 'time')) ?><?php if ($it['ends_at']): ?><small><?= e(fdate($it['ends_at'], 'time')) ?></small><?php endif; ?></span>
              <span class="wl-body"><strong><?= e($it['title']) ?></strong>
                <span class="small muted"><?= e($it['team_name']) ?><?= $it['location'] ? ' · ' . e($it['location']) : '' ?></span></span>
              <?php if (in_array($it['kind'], ['entrenament', 'gimnas'], true) && (int)$it['attendance_count'] > 0): ?><span class="wl-check" title="Llista passada"><?= icon('check', 'icon icon-sm') ?></span><?php endif; ?>
              <?= icon('chevron-right', 'icon icon-sm muted') ?>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>

  <!-- Ordinador: graella horària -->
  <section class="panel panel-flush week-grid-panel">
    <div class="week-grid" style="--hour: <?= $hourPx ?>px">
      <div class="wg-corner"></div>
      <?php foreach ($days as $i => $key): ?>
        <div class="wg-head<?= $key === $today ? ' is-today' : '' ?>">
          <a href="<?= e($link('dia', new DateTime($key))) ?>"><span><?= $dows[$i] ?></span><b><?= (int)substr($key, 8, 2) ?></b></a>
          <?php if ($canManage): ?><a class="wg-add" href="<?= e(url('/calendari/nou', ['data' => $key])) ?>" aria-label="Nou el <?= e(fdate($key, 'day')) ?>"><?= icon('plus', 'icon icon-xs') ?></a><?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="wg-hours" style="height: <?= $gridH ?>px">
        <?php for ($h = $startH; $h < $endH; $h++): ?><span style="top: <?= ($h - $startH) * $hourPx ?>px"><?= sprintf('%02d:00', $h) ?></span><?php endfor; ?>
      </div>
      <?php foreach ($days as $key): ?>
        <div class="wg-col<?= $key === $today ? ' is-today' : '' ?>" style="height: <?= $gridH ?>px">
          <?php if ($key === $today): $nowMin = (int)date('G') * 60 + (int)date('i');
            if ($nowMin >= $startH * 60 && $nowMin <= $endH * 60): ?><span class="wg-now" style="top: <?= round(($nowMin - $startH * 60) / 60 * $hourPx) ?>px"></span><?php endif; endif; ?>
          <?php foreach ($blocks[$key] as $b): $it = $b['it'];
            $top = ($b['s'] - $startH * 60) / 60 * $hourPx; $h = ($b['e'] - $b['s']) / 60 * $hourPx;
            $w = 100 / $b['n']; ?>
            <a class="wg-ev tone-<?= $kindTone[$it['kind']] ?? 'blue' ?><?= $h < 44 ? ' is-short' : '' ?>" href="<?= e($itemUrl($it)) ?>"
               style="top: <?= round($top) ?>px; height: <?= round($h) - 2 ?>px; left: calc(<?= round($w * $b['lane'], 3) ?>% + 2px); width: calc(<?= round($w, 3) ?>% - 4px)"
               title="<?= e(fdate($it['starts_at'], 'time') . ($it['ends_at'] ? '–' . fdate($it['ends_at'], 'time') : '') . ' · ' . $it['title'] . ' · ' . $it['team_name']) ?>">
              <span class="wg-ev-time"><?= e(fdate($it['starts_at'], 'time')) ?><?= $it['ends_at'] ? '–' . e(fdate($it['ends_at'], 'time')) : '' ?></span>
              <strong class="wg-ev-title"><?= e($it['title']) ?></strong>
              <span class="wg-ev-meta"><?= e($it['team_name']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php else: ?>
  <?php if ($count === 0): ?>
    <section class="panel"><?= component('empty_state', ['icon' => 'calendar', 'text' => $calView === 'dia' ? 'No hi ha res programat aquest dia.' : 'No hi ha res programat aquesta setmana.']) ?></section>
  <?php endif; ?>
  <div class="agenda">
    <?php for ($d = clone $from; $d < $to; $d->modify('+1 day')):
        $key = $d->format('Y-m-d');
        $items = $byDay[$key] ?? [];
        if (!$items && ($calView === 'dia' || $count === 0)) { continue; } ?>
      <section class="agenda-day<?= $key === $today ? ' is-today' : '' ?>">
        <h3 class="agenda-date"><a href="<?= e($link('dia', $d)) ?>"><?= e(ucfirst(fdate($key, 'day'))) ?></a></h3>
        <?php if (!$items): ?>
          <p class="muted small agenda-empty">Res programat</p>
        <?php else: ?>
          <ul class="agenda-list">
            <?php foreach ($items as $it): ?>
              <li>
                <a class="agenda-item kind-<?= e($it['kind']) ?>" href="<?= e($itemUrl($it)) ?>">
                  <span class="agenda-time"><?= e(fdate($it['starts_at'], 'time')) ?><?php if ($it['ends_at']): ?><small><?= e(fdate($it['ends_at'], 'time')) ?></small><?php endif; ?></span>
                  <span class="agenda-body">
                    <strong><?= e($it['title']) ?></strong>
                    <span class="muted small"><span class="team-dot" style="--team: <?= e($it['color']) ?>"></span><?= $it['kind'] === 'gimnas' ? 'Gimnàs · ' : '' ?><?= e($it['team_name']) ?><?= $it['location'] ? ' · ' . e($it['location']) : '' ?></span>
                  </span>
                  <span class="pill pill-<?= ['partit' => 'green', 'especial' => 'orange', 'gimnas' => 'violet'][$it['kind']] ?? 'blue' ?>"><?= e($kinds[$it['kind']] ?? '') ?></span>
                  <?php if (in_array($it['kind'], ['entrenament', 'gimnas'], true) && (int)$it['attendance_count'] > 0): ?>
                    <span class="pill pill-muted" title="Llista passada"><?= icon('check', 'icon icon-xs') ?> Llista</span>
                  <?php endif; ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    <?php endfor; ?>
  </div>
<?php endif; ?>
