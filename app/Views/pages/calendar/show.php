<?php
/** @var array $event @var bool $canManage @var bool $canAttend @var array $attendance @var array $sessions */
$isTraining = $event['type'] === 'entrenament';
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url('/calendari', ['vista' => 'setmana', 'data' => substr((string)$event['starts_at'], 0, 10)])) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Calendari</a>

  <section class="panel event-hero">
    <span class="date-chip date-chip-lg">
      <span class="date-chip-month"><?= e(fdate($event['starts_at'], 'monthshort')) ?></span>
      <span class="date-chip-day"><?= e(fdate($event['starts_at'], 'daynum')) ?></span>
    </span>
    <div class="event-hero-body">
      <span class="pill <?= $isTraining ? 'pill-blue' : 'pill-orange' ?>"><?= $isTraining ? 'Entrenament' : 'Esdeveniment especial' ?></span>
      <h2 class="module-title"><?= e($event['title']) ?></h2>
      <ul class="meta-list">
        <li><?= icon('calendar', 'icon icon-sm') ?> <?= e(ucfirst(fdate($event['starts_at'], 'day'))) ?></li>
        <li><?= icon('clock', 'icon icon-sm') ?> <?= e(fdate($event['starts_at'], 'time')) ?><?= $event['ends_at'] ? ' – ' . e(fdate($event['ends_at'], 'time')) : '' ?></li>
        <li><span class="team-dot" style="--team: <?= e($event['color']) ?>"></span><?= e($event['team_name']) ?></li>
        <?php if ($event['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <?= e($event['location']) ?></li><?php endif; ?>
      </ul>
    </div>
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

    <?php if (App\Core\Gate::allows('trainings.view')): ?>
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
      <form method="post" action="<?= e(url('/calendari/' . (int)$event['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar «<?= e($event['title']) ?>»?<?= $attendance['total'] > 0 ? ' També s\'esborrarà la llista d\'assistència.' : '' ?>">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"><?= icon('x', 'icon icon-sm') ?> Elimina</button>
      </form>
    </section>
  <?php endif; ?>
</div>
