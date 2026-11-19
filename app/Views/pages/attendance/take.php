<?php
/** @var array $event @var array $sheet @var array $away @var array $awayList */
use App\Repositories\AttendanceRepository;
$labels = ['present' => ['check', 'Present'], 'retard' => ['clock', 'Retard'], 'justificat' => ['clipboard', 'Justificada'], 'absent' => ['x', 'Absent']];
?>
<div class="take-page">
  <a class="back-link" href="<?= e(url('/assistencia')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Assistència</a>

  <section class="panel take-head">
    <div>
      <h2 class="panel-title"><?= e($event['title']) ?></h2>
      <p class="muted small"><span class="team-dot" style="--team: <?= e($event['color']) ?>"></span><?= e($event['team_name']) ?> · <?= e(ucfirst(fdate($event['starts_at'], 'full'))) ?></p>
    </div>
    <button type="button" class="btn btn-sm" data-all-present><?= icon('check', 'icon icon-sm') ?> Tots presents</button>
  </section>

  <?php if (!$sheet): ?>
    <section class="panel"><?= component('empty_state', ['icon' => 'users', 'text' => 'Aquest equip encara no té jugadors/es.']) ?></section>
  <?php else: ?>
    <form method="post" action="<?= e(url('/assistencia/' . (int)$event['id'])) ?>" class="take-form" data-attendance>
      <?= csrf_field() ?>
      <ul class="roll">
        <?php foreach ($sheet as $p): $pid = (int)$p['id']; $st = (string)$p['status']; $obs = (string)$p['observation'];
            $ab = $away[$pid] ?? null;
            if ($ab && $st === '') { $st = 'justificat'; if ($obs === '') { $obs = App\Repositories\AbsenceRepository::label($ab) . ($ab['note'] ? ': ' . $ab['note'] : ''); } } ?>
          <li class="roll-item<?= $st !== '' ? ' st-' . e($st) : '' ?><?= $ab ? ' is-away' : '' ?>"<?= $ab ? ' data-away' : '' ?>>
            <div class="roll-top">
              <span class="dorsal"><?= $p['dorsal'] !== null ? (int)$p['dorsal'] : '–' ?></span>
              <span class="roll-name">
                <strong><?= e($p['first_name'] . ' ' . $p['last_name']) ?></strong>
                <?php if ($p['position'] === 'porter'): ?><span class="pill pill-orange">Porter/a</span><?php endif; ?>
                <?php if ((int)$p['guest'] === 1): ?><span class="pill pill-muted">Altre equip</span><?php endif; ?>
                <?php if ($ab): ?><span class="pill pill-red"><?= e(App\Repositories\AbsenceRepository::label($ab)) ?> · <?= e(App\Repositories\AbsenceRepository::period($ab)) ?></span><?php endif; ?>
              </span>
              <button type="button" class="icon-btn roll-note-btn<?= $obs !== '' ? ' has-note' : '' ?>" data-note-toggle aria-label="Observació per a <?= e($p['first_name']) ?>"><?= icon('clipboard', 'icon icon-sm') ?></button>
            </div>
            <div class="roll-options" role="radiogroup" aria-label="Assistència de <?= e($p['first_name'] . ' ' . $p['last_name']) ?>">
              <?php foreach ($labels as $key => [$short, $long]): ?>
                <label class="opt opt-<?= $key ?>">
                  <input type="radio" name="status[<?= $pid ?>]" value="<?= $key ?>"<?= $st === $key ? ' checked' : '' ?>>
                  <span><?= icon($short, 'icon icon-sm') ?><em><?= $long ?></em></span>
                </label>
              <?php endforeach; ?>
              <label class="opt opt-clear" title="Sense marcar">
                <input type="radio" name="status[<?= $pid ?>]" value=""<?= $st === '' ? ' checked' : '' ?>>
                <span><?= icon('x', 'icon icon-xs') ?></span>
              </label>
            </div>
            <input class="roll-note" type="text" name="observation[<?= $pid ?>]" value="<?= e($obs) ?>" maxlength="255" placeholder="Observació (ex.: lesió, examen…)"<?= $obs !== '' ? '' : ' hidden' ?>>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="take-footer">
        <span class="take-count" data-count aria-live="polite"></span>
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Desa la llista</button>
      </div>
    </form>
  <?php endif; ?>
</div>
