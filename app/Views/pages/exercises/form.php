<?php
/** @var array|null $ex */
use App\Repositories\ExerciseRepository as Ex;
$isEdit = $ex !== null;
$v = function (string $k, string $d = '') use ($ex): string { return form_val($ex, $k, $d); };
$back = $isEdit ? '/exercicis/' . (int)$ex['id'] : '/exercicis';
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url($back)) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> <?= $isEdit ? e($ex['name']) : 'Exercicis' ?></a>
  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/exercicis/' . (int)$ex['id'] : '/exercicis')) ?>">
      <?= csrf_field() ?>
      <label class="field"><span>Nom</span>
        <input type="text" name="name" value="<?= e($v('name')) ?>" required maxlength="120" placeholder="Ex.: Rondo 4x2 amb pressió" autofocus></label>
      <div class="field-row">
        <label class="field"><span>Categoria</span>
          <select name="category" required>
            <option value="">Tria…</option>
            <?php foreach (Ex::CATEGORIES as $k => [$label]): ?>
              <option value="<?= $k ?>"<?= $v('category') === $k ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select></label>
        <fieldset class="field">
          <legend>Dificultat</legend>
          <div class="segmented">
            <?php foreach (Ex::DIFFICULTY as $n => $label): ?>
              <label><input type="radio" name="difficulty" value="<?= $n ?>"<?= $v('difficulty', '2') === (string)$n ? ' checked' : '' ?>><span><?= $label ?></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      </div>
      <label class="field"><span>Objectiu</span>
        <input type="text" name="objective" value="<?= e($v('objective')) ?>" maxlength="255" placeholder="Què es treballa amb aquest exercici"></label>
      <label class="field"><span>Descripció</span>
        <textarea name="description" rows="6" maxlength="5000" placeholder="Organització, desenvolupament, variants…"><?= e($v('description')) ?></textarea></label>
      <div class="field-row field-row-3">
        <label class="field"><span>Durada (min)</span>
          <input type="number" name="duration_min" value="<?= e($v('duration_min')) ?>" min="1" max="240" inputmode="numeric"></label>
        <label class="field"><span>Edat recomanada</span>
          <input type="text" name="recommended_age" value="<?= e($v('recommended_age')) ?>" maxlength="60" placeholder="Ex.: Infantil i més grans"></label>
        <label class="field"><span>Material</span>
          <input type="text" name="material" value="<?= e($v('material')) ?>" maxlength="255" placeholder="Pilotes, cons…"></label>
      </div>
      <label class="field"><span>Imatge o vídeo <small class="muted">(enllaç https: YouTube o una imatge)</small></span>
        <input type="url" name="media_url" value="<?= e($v('media_url')) ?>" maxlength="500" placeholder="https://www.youtube.com/watch?v=…"></label>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Crea l\'exercici' ?></button>
        <a class="btn" href="<?= e(url($back)) ?>">Cancel·la</a>
      </div>
    </form>
  </section>
</div>
