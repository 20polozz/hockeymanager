<?php
/** @var array|null $team @var array $categories */
$isEdit = $team !== null;
$val = function (string $key, string $default = '') use ($team): string {
    $old = old($key, "\0");
    if ($old !== "\0") {
        return $old;
    }
    return $team !== null && isset($team[$key]) ? (string)$team[$key] : $default;
};
$palette = ['#1e3a8a', '#0f766e', '#7c3aed', '#c2410c', '#be123c', '#0369a1', '#15803d', '#a16207', '#334155', '#db2777'];
$color = $val('color', '#1e3a8a');
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url('/equips')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Equips</a>

  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/equips/' . (int)$team['id'] : '/equips')) ?>">
      <?= csrf_field() ?>
      <label class="field"><span>Nom de l'equip</span>
        <input type="text" name="name" value="<?= e($val('name')) ?>" required minlength="2" maxlength="80" placeholder="Ex.: Fem 17" autofocus></label>

      <label class="field"><span>Categoria</span>
        <select name="category_id">
          <option value="0">Sense categoria</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"<?= $val('category_id') === (string)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <small class="muted">Falta alguna categoria? <a href="<?= e(url('/equips/categories')) ?>">Gestiona les categories</a>.</small></label>

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

      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Crea l\'equip' ?></button>
        <a class="btn" href="<?= e(url('/equips')) ?>">Cancel·la</a>
      </div>
    </form>
  </section>

  <?php if ($isEdit): ?>
    <section class="panel danger-zone">
      <div>
        <h2 class="panel-title">Eliminar l'equip</h2>
        <p class="muted small">Només es pot eliminar un equip sense jugadors/es, partits ni entrenaments.</p>
      </div>
      <form method="post" action="<?= e(url('/equips/' . (int)$team['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar l'equip «<?= e($team['name']) ?>»?">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"><?= icon('x', 'icon icon-sm') ?> Elimina</button>
      </form>
    </section>
  <?php endif; ?>
</div>
