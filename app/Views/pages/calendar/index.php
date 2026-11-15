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
$kinds = ['entrenament' => 'Entrenament', 'partit' => 'Partit', 'especial' => 'Especial'];
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
          <a class="cal-daynum" href="<?= e($link('dia', $d)) ?>" aria-label="<?= e(fdate($key, 'day')) ?>"><?= $d->format('j') ?></a>
          <?php foreach (array_slice($items, 0, 3) as $it): ?>
            <a class="cal-item kind-<?= e($it['kind']) ?>" href="<?= e($itemUrl($it)) ?>" title="<?= e($it['team_name'] . ' · ' . $it['title']) ?>">
              <span class="cal-item-time"><?= e(fdate($it['starts_at'], 'time')) ?></span>
              <span class="cal-item-title"><?= e($team > 0 || $it['kind'] === 'partit' ? $it['title'] : $it['team_name']) ?></span>
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
                    <span class="muted small"><span class="team-dot" style="--team: <?= e($it['color']) ?>"></span><?= e($it['team_name']) ?><?= $it['location'] ? ' · ' . e($it['location']) : '' ?></span>
                  </span>
                  <span class="pill pill-<?= $it['kind'] === 'partit' ? 'green' : ($it['kind'] === 'especial' ? 'orange' : 'blue') ?>"><?= e($kinds[$it['kind']] ?? '') ?></span>
                  <?php if ($it['kind'] === 'entrenament' && (int)$it['attendance_count'] > 0): ?>
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
