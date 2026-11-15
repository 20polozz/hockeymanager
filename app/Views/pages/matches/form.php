<?php
/** @var array|null $m @var array $defaults @var array $teams @var array $competitions */
use App\Repositories\MatchRepository as M;
$isEdit = $m !== null;
$v = function (string $k) use ($m, $defaults): string { return form_val($m, $k, $defaults[$k] ?? ''); };
$back = $isEdit ? '/partits/' . (int)$m['id'] : '/partits';
$status = $v('status') ?: 'programat';
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url($back)) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> <?= $isEdit ? 'Partit' : 'Partits' ?></a>
  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/partits/' . (int)$m['id'] : '/partits')) ?>">
      <?= csrf_field() ?>
      <div class="field-row">
        <label class="field"><span>Equip</span>
          <select name="team_id" required>
            <option value="">Tria un equip…</option>
            <?php foreach ($teams as $t): ?>
              <option value="<?= (int)$t['id'] ?>"<?= $v('team_id') === (string)$t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select></label>
        <label class="field"><span>Rival</span>
          <input type="text" name="opponent" value="<?= e($v('opponent')) ?>" required maxlength="120" placeholder="Ex.: CP Vic"></label>
      </div>
      <div class="field-row field-row-3">
        <label class="field"><span>Data</span><input type="date" name="date" value="<?= e($v('date')) ?>" required></label>
        <label class="field"><span>Hora</span><input type="time" name="time" value="<?= e($v('time')) ?>" required step="300"></label>
        <fieldset class="field">
          <legend>Condició</legend>
          <div class="segmented">
            <label><input type="radio" name="is_home" value="1"<?= $v('is_home') !== '0' ? ' checked' : '' ?>><span>Local</span></label>
            <label><input type="radio" name="is_home" value="0"<?= $v('is_home') === '0' ? ' checked' : '' ?>><span>Visitant</span></label>
          </div>
        </fieldset>
      </div>
      <div class="field-row">
        <label class="field"><span>Competició</span>
          <input type="text" name="competition" value="<?= e($v('competition')) ?>" maxlength="120" list="competitions" placeholder="Ex.: Lliga Nacional Catalana"></label>
        <label class="field"><span>Pista / localització</span>
          <input type="text" name="location" value="<?= e($v('location')) ?>" maxlength="150" placeholder="Ex.: Pavelló Municipal"></label>
      </div>
      <datalist id="competitions"><?php foreach ($competitions as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>

      <fieldset class="field">
        <legend>Estat</legend>
        <div class="segmented">
          <?php foreach (M::STATUSES as $k => [$label]): ?>
            <label><input type="radio" name="status" value="<?= e($k) ?>"<?= $status === $k ? ' checked' : '' ?> data-status><span><?= e($label) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <div class="score-box" id="score-box"<?= $status === 'jugat' ? '' : ' hidden' ?>>
        <label class="field"><span>Gols a favor</span><input type="number" name="goals_for" value="<?= e($v('goals_for')) ?>" min="0" max="99" inputmode="numeric"></label>
        <span class="score-sep">–</span>
        <label class="field"><span>Gols en contra</span><input type="number" name="goals_against" value="<?= e($v('goals_against')) ?>" min="0" max="99" inputmode="numeric"></label>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Crea el partit' ?></button>
        <a class="btn" href="<?= e(url($back)) ?>">Cancel·la</a>
      </div>
    </form>
  </section>
</div>
