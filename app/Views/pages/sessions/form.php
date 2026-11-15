<?php
/** @var array|null $s @var array $defaults @var array $teams @var array $events */
$isEdit = $s !== null;
$v = function (string $k) use ($s, $defaults): string { return form_val($s, $k, $defaults[$k] ?? ''); };
$back = $isEdit ? '/entrenaments/' . (int)$s['id'] : '/entrenaments';
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url($back)) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> <?= $isEdit ? e($s['title']) : 'Entrenaments' ?></a>
  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/entrenaments/' . (int)$s['id'] : '/entrenaments')) ?>">
      <?= csrf_field() ?>
      <label class="field"><span>Títol</span>
        <input type="text" name="title" value="<?= e($v('title')) ?>" required maxlength="120" placeholder="Ex.: Tècnica de passada i finalització" autofocus></label>
      <div class="field-row">
        <label class="field"><span>Equip</span>
          <select name="team_id">
            <option value="0">General (qualsevol equip)</option>
            <?php foreach ($teams as $t): ?>
              <option value="<?= (int)$t['id'] ?>"<?= $v('team_id') === (string)$t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select></label>
        <label class="field"><span>Entrenament del calendari <small class="muted">(opcional)</small></span>
          <select name="event_id">
            <option value="0">Sense vincular</option>
            <?php $cur = $v('event_id'); $found = false; foreach ($events as $ev): if ((string)$ev['id'] === $cur) { $found = true; } ?>
              <option value="<?= (int)$ev['id'] ?>"<?= $cur === (string)$ev['id'] ? ' selected' : '' ?>><?= e(fdate($ev['starts_at'], 'short') . ' ' . fdate($ev['starts_at'], 'time') . ' · ' . $ev['team_name']) ?></option>
            <?php endforeach; ?>
            <?php if ($isEdit && !$found && !empty($s['event_id'])): ?>
              <option value="<?= (int)$s['event_id'] ?>" selected><?= e(fdate($s['event_at'], 'short') . ' · ' . $s['event_title']) ?></option>
            <?php endif; ?>
          </select></label>
      </div>
      <label class="field"><span>Notes <small class="muted">(opcional)</small></span>
        <textarea name="notes" rows="3" maxlength="2000" placeholder="Objectius de la sessió, material, observacions…"><?= e($v('notes')) ?></textarea></label>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Crea la sessió' ?></button>
        <a class="btn" href="<?= e(url($back)) ?>">Cancel·la</a>
      </div>
    </form>
  </section>
</div>
