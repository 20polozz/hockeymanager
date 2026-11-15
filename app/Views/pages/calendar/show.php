<?php
/** @var array $event @var bool $canManage @var bool $canAttend @var array $attendance @var array $sessions @var array $roster @var bool $showStatus @var array $away @var array $removed @var array $pickable */
$isTraining = in_array($event['type'], ['entrenament', 'gimnas'], true);
$typeLabel = App\Repositories\EventRepository::TYPES[$event['type']] ?? '';
$typeTone = ['entrenament' => 'blue', 'gimnas' => 'violet', 'especial' => 'orange'][$event['type']] ?? 'muted';
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url('/calendari', ['vista' => 'setmana', 'data' => substr((string)$event['starts_at'], 0, 10)])) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Calendari</a>

  <section class="panel event-hero">
    <span class="date-chip date-chip-lg">
      <span class="date-chip-month"><?= e(fdate($event['starts_at'], 'monthshort')) ?></span>
      <span class="date-chip-day"><?= e(fdate($event['starts_at'], 'daynum')) ?></span>
    </span>
    <div class="event-hero-body">
      <span class="pill pill-<?= $typeTone ?>"><?= e($typeLabel) ?></span>
      <?php if ($event['series_id']): ?><span class="pill pill-muted"><?= icon('calendar', 'icon icon-xs') ?> Cada setmana</span><?php endif; ?>
      <h2 class="module-title"><?= e($event['title']) ?></h2>
      <ul class="meta-list">
        <li><?= icon('calendar', 'icon icon-sm') ?> <?= e(ucfirst(fdate($event['starts_at'], 'day'))) ?></li>
        <li><?= icon('clock', 'icon icon-sm') ?> <?= e(fdate($event['starts_at'], 'time')) ?><?= $event['ends_at'] ? ' – ' . e(fdate($event['ends_at'], 'time')) : '' ?></li>
        <li><span class="team-dot" style="--team: <?= e($event['color']) ?>"></span><?= e($event['team_name']) ?></li>
        <?php if ($event['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <?= e($event['location']) ?></li><?php endif; ?>
      </ul>
    </div>
  </section>

  <?php if (!empty($event['teams'])): ?>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Equips']) ?>
      <ul class="name-chips">
        <?php foreach ($event['teams'] as $t): ?><li><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><?= e($t['name']) ?></li><?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php
  $called = array_values(array_filter($roster, static function ($p) use ($away) { return !$p['guest'] && !isset($away[(int)$p['id']]); }));
  $awayList = array_values(array_filter($roster, static function ($p) use ($away) { return !$p['guest'] && isset($away[(int)$p['id']]); }));
  $isStaff = App\Core\Gate::allows('attendance.view');
  $guests = array_values(array_filter($roster, static function ($p) { return $p['guest']; }));
  $dotTone = ['present' => 'green', 'retard' => 'blue', 'justificat' => 'orange', 'absent' => 'red'];
  $statusLabels = App\Repositories\AttendanceRepository::STATUSES;
  $editRoster = $canManage;
  $chip = static function (array $p, string $extra = '') use ($showStatus, $dotTone, $statusLabels, $editRoster): string {
      $st = $showStatus && $p['status'] ? $p['status'] : null;
      return '<li class="' . ($editRoster ? 'chip-edit' : '') . $extra . '"' . ($st ? ' title="' . e($statusLabels[$st] ?? '') . '"' : '') . '>'
          . ($st ? '<i class="dot dot-' . ($dotTone[$st] ?? 'muted') . '"></i>' : '')
          . '<span>' . e($p['first_name'] . ' ' . $p['last_name'])
          . ($p['position'] === 'porter' ? ' <span class="muted small">(P)</span>' : '') . '</span>'
          . ($editRoster ? '<button class="chip-x" type="submit" name="players[]" value="' . (int)$p['id'] . '" aria-label="Treu ' . e($p['first_name']) . ' d\'aquest entreno" title="Treu d\'aquest entreno">' . icon('x', 'icon icon-xs') . '</button>' : '')
          . '</li>';
  };
  $inRoster = array_map('intval', array_column($roster, 'id'));
  $removedIds = array_map('intval', array_column($removed, 'id'));
  $toAdd = array_values(array_filter($pickable, static function ($p) use ($inRoster, $removedIds) {
      return !in_array((int)$p['id'], $inRoster, true) && !in_array((int)$p['id'], $removedIds, true);
  }));
  ?>
  <section class="panel" id="convocades">
    <?= component('panel_head', ['title' => 'Jugadores convocades (' . count($called) . ')']) ?>
    <?php if ($editRoster): ?><form method="post" action="<?= e(url('/calendari/' . (int)$event['id'] . '/jugadores')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="treure"><?php endif; ?>
    <?php if ($called): ?>
      <ul class="name-chips">
        <?php foreach ($called as $p) { echo $chip($p); } ?>
      </ul>
    <?php else: ?>
      <p class="muted">Cap jugadora convocada.</p>
    <?php endif; ?>
    <?php if ($awayList): ?>
      <p class="muted small roster-sub"><?= icon('alert', 'icon icon-xs') ?> No disponibles (<?= count($awayList) ?>)</p>
      <ul class="name-chips">
        <?php foreach ($awayList as $p): $a = $away[(int)$p['id']]; ?>
          <li class="chip-off<?= $editRoster ? ' chip-edit' : '' ?>"<?= $isStaff ? ' title="' . e(App\Repositories\AbsenceRepository::label($a) . ' · ' . App\Repositories\AbsenceRepository::period($a)) . '"' : '' ?>><span><?= e($p['first_name'] . ' ' . $p['last_name']) ?><?php if ($isStaff): ?> <span class="muted small">(<?= e(App\Repositories\AbsenceRepository::label($a)) ?>)</span><?php endif; ?></span><?php if ($editRoster): ?><button class="chip-x" type="submit" name="players[]" value="<?= (int)$p['id'] ?>" aria-label="Treu <?= e($p['first_name']) ?>"><?= icon('x', 'icon icon-xs') ?></button><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if ($guests): ?>
      <p class="muted small roster-sub">També hi han vingut (<?= count($guests) ?>)</p>
      <ul class="name-chips"><?php foreach ($guests as $p) { echo $chip($p); } ?></ul>
    <?php endif; ?>
    <?php if ($editRoster): ?></form><?php endif; ?>

    <?php if ($editRoster && $removed): ?>
      <form method="post" action="<?= e(url('/calendari/' . (int)$event['id'] . '/jugadores')) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="afegir">
        <p class="muted small roster-sub">Tretes d'aquest entreno (<?= count($removed) ?>)</p>
        <ul class="name-chips">
          <?php foreach ($removed as $p): ?>
            <li class="chip-edit chip-removed"><span><?= e($p['first_name'] . ' ' . $p['last_name']) ?></span><button class="chip-x chip-undo" type="submit" name="players[]" value="<?= (int)$p['id'] ?>" aria-label="Torna a afegir <?= e($p['first_name']) ?>" title="Torna-la a afegir"><?= icon('plus', 'icon icon-xs') ?></button></li>
          <?php endforeach; ?>
        </ul>
      </form>
    <?php endif; ?>

    <?php if ($editRoster && $toAdd): ?>
      <details class="roster-add">
        <summary class="btn"><?= icon('plus', 'icon icon-sm') ?> Afegeix jugadores</summary>
        <form method="post" action="<?= e(url('/calendari/' . (int)$event['id'] . '/jugadores')) ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="afegir">
          <div class="pick-list">
            <?php foreach ($toAdd as $p): ?>
              <label class="pick"><input type="checkbox" name="players[]" value="<?= (int)$p['id'] ?>" data-pick>
                <span class="pick-name"><?= e($p['first_name'] . ' ' . $p['last_name']) ?> <small class="muted"><?= e((string)$p['teams']) ?></small></span></label>
            <?php endforeach; ?>
          </div>
          <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Afegeix a aquest entreno</button></div>
        </form>
      </details>
    <?php endif; ?>
    <?php if ($editRoster): ?><p class="muted small roster-hint">Els canvis només afecten aquest dia, no la resta de la sèrie ni l'equip.</p><?php endif; ?>
  </section>

  <?php if ($event['notes']): ?>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Notes']) ?>
      <p class="pre-line"><?= e($event['notes']) ?></p>
    </section>
  <?php endif; ?>

  <?php if ($isTraining): ?>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Assistència']) ?>
      <?php if ($attendance['total'] > 0): ?>
        <div class="legend legend-lg">
          <span><i class="dot dot-green"></i><?= $attendance['present'] ?> presents</span>
          <span><i class="dot dot-blue"></i><?= $attendance['retard'] ?> retards</span>
          <span><i class="dot dot-orange"></i><?= $attendance['justificat'] ?> justificades</span>
          <span><i class="dot dot-red"></i><?= $attendance['absent'] ?> absències</span>
        </div>
      <?php else: ?>
        <p class="muted">Encara no s'ha passat llista.</p>
      <?php endif; ?>
      <?php if ($canAttend): ?>
        <a class="btn btn-primary" href="<?= e(url('/assistencia/' . (int)$event['id'])) ?>"><?= icon('checklist', 'icon icon-sm') ?> <?= $attendance['total'] > 0 ? 'Revisa la llista' : 'Passa llista' ?></a>
      <?php endif; ?>
    </section>

    <?php if ($event['type'] === 'entrenament' && App\Core\Gate::allows('trainings.view')): ?>
      <section class="panel">
        <?= component('panel_head', ['title' => 'Sessió d\'entrenament']) ?>
        <?php if ($sessions): foreach ($sessions as $s): ?>
          <a class="person" href="<?= e(url('/entrenaments/' . (int)$s['id'])) ?>"><?= icon('dumbbell', 'icon icon-sm') ?> <?= e($s['title']) ?> <span class="muted small">· <?= (int)$s['total_minutes'] ?> min</span></a>
        <?php endforeach; else: ?>
          <p class="muted">No hi ha cap sessió planificada per a aquest entrenament.</p>
          <?php if (App\Core\Gate::allows('trainings.manage')): ?>
            <a class="btn" href="<?= e(url('/entrenaments/nou', ['esdeveniment' => (int)$event['id']])) ?>"><?= icon('plus', 'icon icon-sm') ?> Planifica la sessió</a>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($canManage): ?>
    <section class="panel danger-zone">
      <a class="btn" href="<?= e(url('/calendari/' . (int)$event['id'] . '/editar')) ?>"><?= icon('wrench', 'icon icon-sm') ?> Edita</a>
      <div class="head-actions">
        <form method="post" action="<?= e(url('/calendari/' . (int)$event['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar «<?= e($event['title']) ?>» d'aquest dia?<?= $attendance['total'] > 0 ? ' També s\'esborrarà la llista d\'assistència.' : '' ?>">
          <?= csrf_field() ?><input type="hidden" name="scope" value="un">
          <button class="btn btn-danger" type="submit"><?= icon('x', 'icon icon-sm') ?> Elimina aquest dia</button>
        </form>
        <?php if ((int)$event['later_in_series'] > 0): ?>
          <form method="post" action="<?= e(url('/calendari/' . (int)$event['id'] . '/eliminar')) ?>" data-confirm="S'eliminarà aquest dia i els <?= (int)$event['later_in_series'] ?> següents de la sèrie. Continuar?">
            <?= csrf_field() ?><input type="hidden" name="scope" value="serie">
            <button class="btn btn-danger" type="submit">Elimina aquest i els següents (<?= (int)$event['later_in_series'] + 1 ?>)</button>
          </form>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
