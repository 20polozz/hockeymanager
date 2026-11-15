<?php
/** @var array|null $event @var array $defaults @var array $teams @var array $players */
$isEdit = $event !== null;
$v = function (string $key) use ($event, $defaults): string {
    return form_val($event, $key, $defaults[$key] ?? '');
};
$type = $v('type') ?: 'entrenament';
$back = $isEdit ? '/calendari/' . (int)$event['id'] : '/calendari';
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url($back)) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> <?= $isEdit ? e($event['title']) : 'Calendari' ?></a>
  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/calendari/' . (int)$event['id'] : '/calendari')) ?>">
      <?= csrf_field() ?>
      <fieldset class="field">
        <legend>Tipus</legend>
        <div class="segmented">
          <label><input type="radio" name="type" value="entrenament"<?= $type === 'entrenament' ? ' checked' : '' ?>><span>Entrenament</span></label>
          <label><input type="radio" name="type" value="gimnas"<?= $type === 'gimnas' ? ' checked' : '' ?>><span>Gimnàs</span></label>
          <label><input type="radio" name="type" value="especial"<?= $type === 'especial' ? ' checked' : '' ?>><span>Especial</span></label>
        </div>
        <small class="muted">Els partits es creen des de l'apartat Partits.</small>
      </fieldset>

      <label class="field"><span>Per a</span>
        <select name="team_id" required>
          <option value="">Tria…</option>
          <option value="club"<?= $v('team_id') === 'club' ? ' selected' : '' ?>>Tot el club</option>
          <?php foreach ($teams as $t): ?>
            <option value="<?= (int)$t['id'] ?>"<?= $v('team_id') === (string)$t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
          <?php endforeach; ?>
        </select></label>

      <?php $who = $v('who') ?: 'tots'; $oldP = old_array('participants');
            $checkedP = $oldP !== null ? array_map('intval', $oldP) : array_map('intval', array_column($event['participants'] ?? [], 'id')); ?>
      <fieldset class="field">
        <legend>Qui hi ha d'anar</legend>
        <div class="segmented">
          <label><input type="radio" name="who" value="tots"<?= $who !== 'llista' ? ' checked' : '' ?> data-who><span>Tot l'equip / tot el club</span></label>
          <label><input type="radio" name="who" value="llista"<?= $who === 'llista' ? ' checked' : '' ?> data-who><span>Només unes jugadores</span></label>
        </div>
      </fieldset>
      <fieldset class="field" id="who-list"<?= $who === 'llista' ? '' : ' hidden' ?>>
        <legend>Jugadores <small class="muted" data-pick-count data-pick-label="seleccionades"></small></legend>
        <div class="pick-list">
          <?php foreach ($players as $p): $on = in_array((int)$p['id'], $checkedP, true); ?>
            <label class="pick<?= $on ? ' is-on' : '' ?>"><input type="checkbox" name="participants[]" value="<?= (int)$p['id'] ?>"<?= $on ? ' checked' : '' ?> data-pick>
              <span class="pick-name"><?= e($p['first_name'] . ' ' . $p['last_name']) ?> <small class="muted"><?= e((string)$p['teams']) ?></small></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <label class="field"><span>Títol <small class="muted">(si el deixes buit: «Entrenament + equip» o «Gimnàs»)</small></span>
        <input type="text" name="title" value="<?= e($v('title')) ?>" maxlength="120" placeholder="Ex.: Gym grup 1"></label>

      <div class="field-row field-row-3">
        <label class="field"><span>Data</span>
          <input type="date" name="date" value="<?= e($v('date')) ?>" required></label>
        <label class="field"><span>Inici</span>
          <input type="time" name="start_time" value="<?= e($v('start_time')) ?>" required step="300"></label>
        <label class="field"><span>Final <small class="muted">(opcional)</small></span>
          <input type="time" name="end_time" value="<?= e($v('end_time')) ?>" step="300"></label>
      </div>

      <label class="field"><span>Lloc <small class="muted">(opcional)</small></span>
        <input type="text" name="location" value="<?= e($v('location')) ?>" maxlength="150" placeholder="Ex.: Pavelló Municipal"></label>

      <label class="field"><span>Notes <small class="muted">(opcional)</small></span>
        <textarea name="notes" rows="3" maxlength="2000"><?= e($v('notes')) ?></textarea></label>

      <?php if (!$isEdit): ?>
        <div class="repeat-box">
          <label class="check check-inline">
            <input type="checkbox" name="repeat" value="1"<?= old('repeat') ? ' checked' : '' ?> data-reveal="repeat-until">
            <span>Repeteix cada setmana</span>
          </label>
          <label class="field" id="repeat-until"<?= old('repeat') ? '' : ' hidden' ?>><span>Fins al dia</span>
            <input type="date" name="repeat_until" value="<?= e(old('repeat_until')) ?>"></label>
        </div>
      <?php endif; ?>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Crea' ?></button>
        <a class="btn" href="<?= e(url($back)) ?>">Cancel·la</a>
      </div>
    </form>
  </section>
</div>
