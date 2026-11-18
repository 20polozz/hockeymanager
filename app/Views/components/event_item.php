<?php
/**
 * Element de llista d'esdeveniment (data + títol + equip).
 * @var array $event  kind, title, starts_at, location, team_name, color
 */
$kinds = ['entrenament' => ['Entrenament', 'blue'], 'especial' => ['Especial', 'orange'], 'gimnas' => ['Gimnàs', 'violet']];
[$kindLabel, $kindTone] = $kinds[$event['kind']] ?? ['Esdeveniment', 'muted'];
?>
<li class="event-item">
  <span class="date-chip">
    <span class="date-chip-month"><?= e(fdate($event['starts_at'], 'monthshort')) ?></span>
    <span class="date-chip-day"><?= e(fdate($event['starts_at'], 'daynum')) ?></span>
  </span>
  <span class="event-body">
    <span class="event-title"><?= e($event['title']) ?></span>
    <span class="event-meta">
      <span class="team-dot" style="--team: <?= e($event['color']) ?>"></span><?= e($event['team_name']) ?>
      · <?= icon('clock', 'icon icon-xs') ?> <?= e(fdate($event['starts_at'], 'time')) ?>
    </span>
  </span>
  <span class="pill pill-<?= e($kindTone) ?>"><?= e($kindLabel) ?></span>
</li>
