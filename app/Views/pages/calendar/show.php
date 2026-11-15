<?php
/** @var array $event @var bool $canManage @var bool $canAttend @var array $attendance @var array $sessions @var array $roster @var bool $showStatus */
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
  $called = array_values(array_filter($roster, static function ($p) { return !$p['guest']; }));
  $guests = array_values(array_filter($roster, static function ($p) { return $p['guest']; }));
  $dotTone = ['present' => 'green', 'retard' => 'blue', 'justificat' => 'orange', 'absent' => 'red'];
  $statusLabels = App\Repositories\AttendanceRepository::STATUSES;
  $chip = static function (array $p) use ($showStatus, $dotTone, $statusLabels): string {
      $st = $showStatus && $p['status'] ? $p['status'] : null;
      return '<li' . ($st ? ' title="' . e($statusLabels[$st] ?? '') . '"' : '') . '>'
          . ($st ? '<i class="dot dot-' . ($dotTone[$st] ?? 'muted') . '"></i>' : '')
          . e($p['first_name'] . ' ' . $p['last_name'])
          . ($p['position'] === 'porter' ? ' <span class="muted small">(P)</span>' : '') . '</li>';
  };
  ?>
  <section class="panel">
    <?= component('panel_head', ['title' => 'Jugadores convocades (' . count($called) . ')']) ?>
    <?php if ($called): ?>
      <ul class="name-chips">
        <?php foreach ($called as $p) { echo $chip($p); } ?>
      </ul>
    <?php else: ?>
      <p class="muted">Cap jugadora assignada. Afegeix-les a l'equip o edita l'esdeveniment.</p>
    <?php endif; ?>
    <?php if ($guests): ?>
      <p class="muted small" style="margin:12px 0 6px">També hi han vingut (<?= count($guests) ?>)</p>
      <ul class="name-chips"><?php foreach ($guests as $p) { echo $chip($p); } ?></ul>
    <?php endif; ?>
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
