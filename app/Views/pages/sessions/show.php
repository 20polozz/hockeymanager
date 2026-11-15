<?php
/** @var array $s @var array $library @var bool $canManage */
use App\Repositories\ExerciseRepository as Ex;
$items = $s['items'];
$action = url('/entrenaments/' . (int)$s['id'] . '/exercicis');
?>
<div class="form-page form-page-wide">
  <a class="back-link" href="<?= e(url('/entrenaments')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Entrenaments</a>

  <section class="panel session-hero">
    <div>
      <div class="chips">
        <?php if ($s['team_name']): ?><span class="pill pill-muted"><span class="team-dot" style="--team: <?= e($s['color']) ?>"></span><?= e($s['team_name']) ?></span><?php else: ?><span class="pill pill-muted">General</span><?php endif; ?>
        <?php if ($s['event_id']): ?><a class="pill pill-blue" href="<?= e(url('/calendari/' . (int)$s['event_id'])) ?>"><?= icon('calendar', 'icon icon-xs') ?> <?= e(fdate($s['event_at'], 'short') . ' ' . fdate($s['event_at'], 'time')) ?></a><?php endif; ?>
      </div>
      <h2 class="module-title"><?= e($s['title']) ?></h2>
      <?php if ($s['notes']): ?><p class="pre-line muted"><?= e($s['notes']) ?></p><?php endif; ?>
    </div>
    <div class="session-total">
      <strong><?= (int)$s['total_minutes'] ?></strong><span>minuts</span>
      <small><?= count($items) ?> exercicis</small>
    </div>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Exercicis de la sessió']) ?>
    <?php if (!$items): ?>
      <?= component('empty_state', ['icon' => 'cone', 'text' => 'Encara no hi ha exercicis. Afegeix-ne des de la biblioteca.']) ?>
    <?php else: ?>
      <ol class="plan">
        <?php foreach ($items as $i => $it): [$catLabel, $tone] = Ex::CATEGORIES[$it['category']]; $mins = $it['duration_min'] ?? $it['default_minutes']; ?>
          <li class="plan-item" id="item-<?= (int)$it['id'] ?>">
            <span class="plan-num"><?= $i + 1 ?></span>
            <div class="plan-body">
              <div class="plan-top">
                <a class="plan-name" href="<?= e(url('/exercicis/' . (int)$it['exercise_id'])) ?>"><?= e($it['name']) ?></a>
                <span class="ex-cat tone-<?= $tone ?>"><?= e($catLabel) ?></span>
                <span class="plan-min"><?= icon('clock', 'icon icon-xs') ?> <?= $mins !== null ? (int)$mins . ' min' : '—' ?></span>
              </div>
              <?php if ($it['notes']): ?><p class="small plan-notes"><?= e($it['notes']) ?></p><?php endif; ?>
              <?php if ($canManage): ?>
                <details class="plan-edit">
                  <summary class="small">Edita durada i notes</summary>
                  <form method="post" action="<?= e($action) ?>" class="plan-edit-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save"><input type="hidden" name="item" value="<?= (int)$it['id'] ?>">
                    <label><span class="visually-hidden">Minuts</span><input type="number" name="duration_min" min="1" max="240" value="<?= e((string)($it['duration_min'] ?? '')) ?>" placeholder="<?= e((string)($it['default_minutes'] ?? 'min')) ?>"></label>
                    <label class="grow"><span class="visually-hidden">Notes</span><input type="text" name="notes" maxlength="255" value="<?= e((string)$it['notes']) ?>" placeholder="Notes per a aquesta sessió"></label>
                    <button class="btn btn-sm" type="submit">Desa</button>
                  </form>
                </details>
              <?php endif; ?>
            </div>
            <?php if ($canManage): ?>
              <div class="plan-actions">
                <?php foreach ([['up', 'Puja', 'chevron-right', 'rot-up', $i === 0], ['down', 'Baixa', 'chevron-right', 'rot-down', $i === count($items) - 1], ['remove', 'Treu', 'x', '', false]] as [$act, $label, $ic, $cls, $disabled]): ?>
                  <form method="post" action="<?= e($action) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $act ?>"><input type="hidden" name="item" value="<?= (int)$it['id'] ?>">
                    <button class="icon-btn bordered<?= $act === 'remove' ? ' danger' : '' ?>" type="submit" aria-label="<?= e($label . ' ' . $it['name']) ?>"<?= $disabled ? ' disabled' : '' ?>><?= icon($ic, 'icon icon-sm ' . $cls) ?></button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

    <?php if ($canManage): ?>
      <form method="post" action="<?= e($action) ?>" class="add-ex">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <label class="field grow"><span>Afegeix un exercici</span>
          <select name="exercise_id" required>
            <option value="">Tria de la biblioteca…</option>
            <?php $lastCat = null; foreach ($library as $x):
                if ($x['category'] !== $lastCat) { if ($lastCat !== null) { echo '</optgroup>'; } echo '<optgroup label="' . e(Ex::CATEGORIES[$x['category']][0]) . '">'; $lastCat = $x['category']; } ?>
              <option value="<?= (int)$x['id'] ?>"><?= e($x['name']) ?><?= $x['duration_min'] ? ' (' . (int)$x['duration_min'] . ' min)' : '' ?></option>
            <?php endforeach; if ($lastCat !== null) { echo '</optgroup>'; } ?>
          </select></label>
        <button class="btn btn-primary" type="submit"><?= icon('plus', 'icon icon-sm') ?> Afegeix</button>
      </form>
      <?php if (!$library): ?><p class="muted small">La biblioteca és buida: <a href="<?= e(url('/exercicis/nou')) ?>">crea el primer exercici</a>.</p><?php endif; ?>
    <?php endif; ?>
  </section>

  <?php if ($canManage): ?>
    <section class="panel danger-zone">
      <a class="btn" href="<?= e(url('/entrenaments/' . (int)$s['id'] . '/editar')) ?>"><?= icon('wrench', 'icon icon-sm') ?> Edita la sessió</a>
      <form method="post" action="<?= e(url('/entrenaments/' . (int)$s['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar la sessió «<?= e($s['title']) ?>»?">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"><?= icon('x', 'icon icon-sm') ?> Elimina</button>
      </form>
    </section>
  <?php endif; ?>
</div>
