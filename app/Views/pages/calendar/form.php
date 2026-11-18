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
      </fieldset>

      <?php $who = $v('who') ?: 'equips';
            $oldT = old_array('teams'); $checkedT = $oldT !== null ? array_map('intval', $oldT) : array_map('intval', (array)($defaults['teams'] ?? []));
            $oldP = old_array('participants');
            $checkedP = $oldP !== null ? array_map('intval', $oldP) : array_map('intval', array_column($event['participants'] ?? [], 'id')); ?>
      <fieldset class="field">
        <legend>Per a</legend>
        <div class="segmented">
          <label><input type="radio" name="who" value="equips"<?= $who !== 'llista' ? ' checked' : '' ?> data-who><span>Equip/s</span></label>
          <label><input type="radio" name="who" value="llista"<?= $who === 'llista' ? ' checked' : '' ?> data-who><span>Jugador/es</span></label>
        </div>
      </fieldset>
      <fieldset class="field" id="who-teams"<?= $who === 'llista' ? ' hidden' : '' ?>>
        <legend>Equips <small class="muted">(per a un entrenament conjunt, marca'ls tots)</small></legend>
        <div class="team-checks">
          <?php foreach ($teams as $t): $tid = (int)$t['id']; $on = in_array($tid, $checkedT, true); ?>
            <div class="team-check<?= $on ? ' is-on' : '' ?>">
              <label class="team-check-main"><input type="checkbox" name="teams[]" value="<?= $tid ?>"<?= $on ? ' checked' : '' ?> data-toggle-row><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><span><?= e($t['name']) ?></span></label>
            </div>
          <?php endforeach; ?>
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

      <label class="field"><span>Títol <small class="muted">(ex.: «Entreno a pista», «GYM grup 1»)</small></span>
        <input type="text" name="title" value="<?= e($v('title')) ?>" maxlength="120" placeholder="Ex.: Entreno a pista"></label>

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
