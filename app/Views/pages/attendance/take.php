<?php
/** @var array $event @var array $sheet @var array $away */
use App\Repositories\AttendanceRepository;
?>
<div class="take-page">
  <a class="back-link" href="<?= e(url('/assistencia')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Assistència</a>

  <section class="panel take-head">
    <div>
      <h2 class="panel-title"><?= e($event['title']) ?></h2>
      <p class="muted small"><span class="team-dot" style="--team: <?= e($event['color']) ?>"></span><?= e($event['team_name']) ?> · <?= e(ucfirst(fdate($event['starts_at'], 'full'))) ?></p>
    </div>
  </section>

  <?php if (!$sheet): ?>
    <section class="panel"><?= component('empty_state', ['icon' => 'users', 'text' => 'Aquest entrenament encara no té jugadores.']) ?></section>
  <?php else: ?>
    <p class="take-help"><?= icon('info', 'icon icon-sm') ?><span>Totes surten com a <strong>presents</strong>. Toca qui no ha vingut o ha arribat tard per canviar-ho.</span></p>
    <form method="post" action="<?= e(url('/assistencia/' . (int)$event['id'])) ?>" class="take-form" data-attendance>
      <?= csrf_field() ?>
      <ul class="roll2">
        <?php foreach ($sheet as $p): $pid = (int)$p['id']; $st = (string)$p['status']; $obs = (string)$p['observation'];
            $ab = $away[$pid] ?? null;
            if ($st === '') {
                if ($ab) { $st = 'justificat'; if ($obs === '') { $obs = App\Repositories\AbsenceRepository::label($ab) . ($ab['note'] ? ': ' . $ab['note'] : ''); } }
                else { $st = 'present'; }
            } ?>
          <li>
            <button type="button" class="roll2-item st-<?= e($st) ?>" data-roll="<?= $pid ?>" aria-haspopup="dialog">
              <span class="roll2-icon" aria-hidden="true"></span>
              <span class="roll2-body">
                <strong><?= e($p['first_name'] . ' ' . $p['last_name']) ?></strong>
                <span class="roll2-tags">
                  <?php if ($p['position'] === 'porter'): ?><span class="pill pill-orange">Porter/a</span><?php endif; ?>
                  <?php if ((int)$p['guest'] === 1): ?><span class="pill pill-muted">Altre equip</span><?php endif; ?>
                  <?php if ($ab): ?><span class="pill pill-red"><?= e(App\Repositories\AbsenceRepository::label($ab)) ?></span><?php endif; ?>
                  <span class="roll2-note" data-note-text><?= e($obs) ?></span>
                </span>
              </span>
              <span class="roll2-status" data-status-text></span>
              <?= icon('chevron-right', 'icon icon-sm roll2-chev') ?>
            </button>
            <input type="hidden" name="status[<?= $pid ?>]" value="<?= e($st) ?>" data-status-input>
            <input type="hidden" name="observation[<?= $pid ?>]" value="<?= e($obs) ?>" data-note-input>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="take-footer">
        <span class="take-count" data-count aria-live="polite"></span>
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Desa la llista</button>
      </div>
    </form>

    <div class="roll-sheet" data-roll-sheet hidden>
      <div class="roll-sheet-backdrop" data-sheet-close></div>
      <div class="roll-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="roll-sheet-title">
        <div class="roll-sheet-grip" aria-hidden="true"></div>
        <h3 id="roll-sheet-title" class="roll-sheet-title" data-sheet-name></h3>
        <div class="roll-sheet-options">
          <?php foreach (['present' => ['check', 'Present', 'Ha vingut'], 'retard' => ['clock', 'Retard', 'Ha arribat tard'], 'justificat' => ['clipboard', 'Justificada', 'No ha vingut però ha avisat'], 'absent' => ['x', 'Absent', 'No ha vingut ni avisat']] as $k => [$ic, $lbl, $desc]): ?>
            <button type="button" class="roll-opt st-<?= $k ?>" data-set="<?= $k ?>">
              <span class="roll-opt-icon"><?= icon($ic) ?></span>
              <span><strong><?= $lbl ?></strong><small><?= $desc ?></small></span>
            </button>
          <?php endforeach; ?>
        </div>
        <label class="field"><span>Observació <small class="muted">(opcional)</small></span>
          <input type="text" maxlength="255" placeholder="Ex.: lesió, examen, metge…" data-sheet-note></label>
        <div class="roll-sheet-actions">
          <button type="button" class="btn btn-sm btn-ghost" data-set="">Treu-la d'aquesta llista</button>
          <button type="button" class="btn btn-primary" data-sheet-close>Fet</button>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
