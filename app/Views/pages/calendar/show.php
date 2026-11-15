<?php
/** @var array $event @var bool $canManage @var bool $canAttend @var array $attendance @var array $sessions */
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
        <li><span class="team-dot" style="--team: <?= e($event['color']) ?>"></span><?= $event['participants'] && $event['team_id'] === null ? count($event['participants']) . ' jugadores' : e($event['team_name']) ?></li>
        <?php if ($event['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <?= e($event['location']) ?></li><?php endif; ?>
      </ul>
    </div>
  </section>

  <?php if ($event['participants']): ?>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Llista de jugadores (' . count($event['participants']) . ')']) ?>
      <ul class="name-chips">
        <?php foreach ($event['participants'] as $p): ?><li><?= e($p['first_name'] . ' ' . $p['last_name']) ?></li><?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

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
