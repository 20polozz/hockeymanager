<?php
/** @var array|null $team @var string $kind @var array $categories @var array $players @var array $members */
$isEdit = $team !== null;
$val = function (string $key, string $default = '') use ($team): string {
    return form_val($team, $key, $default);
};
$palette = ['#1e3a8a', '#0f766e', '#7c3aed', '#c2410c', '#be123c', '#0369a1', '#15803d', '#a16207', '#334155', '#db2777'];
$color = $val('color', '#1e3a8a');
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url('/equips')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Equips</a>

  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/equips/' . (int)$team['id'] : '/equips')) ?>">
      <?= csrf_field() ?>
      <fieldset class="field">
        <legend>Tipus</legend>
        <div class="segmented">
          <label><input type="radio" name="kind" value="equip"<?= $val('kind', $kind) !== 'grup' ? ' checked' : '' ?> data-kind><span>Equip de competició</span></label>
          <label><input type="radio" name="kind" value="grup"<?= $val('kind', $kind) === 'grup' ? ' checked' : '' ?> data-kind><span>Grup d'entrenament (gimnàs…)</span></label>
        </div>
        <small class="muted">Els grups d'entrenament barregen jugadors/es de diferents equips. Surten al calendari i a l'assistència, però no juguen partits.</small>
      </fieldset>

      <label class="field"><span>Nom</span>
        <input type="text" name="name" value="<?= e($val('name')) ?>" required minlength="2" maxlength="80" placeholder="Ex.: Fem 17 o Gym grup 1" autofocus></label>

      <label class="field"><span>Categoria</span>
        <select name="category_id">
          <option value="0">Sense categoria</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"<?= $val('category_id') === (string)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></label>

      <fieldset class="field">
        <legend>Color</legend>
        <div class="swatches">
          <?php foreach ($palette as $hex): ?>
            <label class="swatch" style="--sw: <?= e($hex) ?>">
              <input type="radio" name="color" value="<?= e($hex) ?>"<?= $color === $hex ? ' checked' : '' ?>>
              <span aria-hidden="true"></span><span class="visually-hidden"><?= e($hex) ?></span>
            </label>
          <?php endforeach; ?>
          <?php if (!in_array($color, $palette, true)): ?>
            <label class="swatch" style="--sw: <?= e($color) ?>"><input type="radio" name="color" value="<?= e($color) ?>" checked><span aria-hidden="true"></span></label>
          <?php endif; ?>
        </div>
      </fieldset>

      <?php $oldM = old_array('members'); $checkedM = $oldM !== null ? array_map('intval', $oldM) : $members; ?>
      <fieldset class="field" data-kind-panel="grup"<?= $val('kind', $kind) === 'grup' ? '' : ' hidden' ?>>
        <legend>Membres del grup <small class="muted" data-pick-count data-pick-label="seleccionades"></small></legend>
        <div class="pick-list">
          <?php foreach ($players as $p): $on = in_array((int)$p['id'], $checkedM, true); ?>
            <label class="pick<?= $on ? ' is-on' : '' ?>"><input type="checkbox" name="members[]" value="<?= (int)$p['id'] ?>"<?= $on ? ' checked' : '' ?> data-pick>
              <span class="pick-name"><?= e($p['first_name'] . ' ' . $p['last_name']) ?><?php if ($p['teams']): ?> <small class="muted"><?= e($p['teams']) ?></small><?php endif; ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Crea' ?></button>
        <a class="btn" href="<?= e(url('/equips')) ?>">Cancel·la</a>
      </div>
    </form>
  </section>

  <?php if ($isEdit): ?>
    <section class="panel danger-zone">
      <div>
        <h2 class="panel-title">Eliminar</h2>
        <p class="muted small">Només es pot eliminar si mai ha tingut jugadors/es, partits ni entrenaments.</p>
      </div>
      <form method="post" action="<?= e(url('/equips/' . (int)$team['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar l'equip «<?= e($team['name']) ?>»?">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"><?= icon('x', 'icon icon-sm') ?> Elimina</button>
      </form>
    </section>
  <?php endif; ?>
</div>
