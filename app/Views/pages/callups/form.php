<?php
/** @var array $m @var array|null $callup @var array $selected @var array $eligible */
$own = array_filter($eligible, function ($p) { return (int)$p['own'] === 1; });
$others = array_filter($eligible, function ($p) { return (int)$p['own'] !== 1; });
$sent = $callup && $callup['sent_at'];
$row = function (array $p) use ($selected): string {
    $on = in_array((int)$p['id'], $selected, true);
    return '<label class="pick' . ($on ? ' is-on' : '') . '"><input type="checkbox" name="players[]" value="' . (int)$p['id'] . '"' . ($on ? ' checked' : '') . ' data-pick>'
        . '<span class="dorsal">' . ($p['dorsal'] !== null ? (int)$p['dorsal'] : '–') . '</span>'
        . '<span class="pick-name">' . e($p['first_name'] . ' ' . $p['last_name'])
        . ($p['position'] === 'porter' ? ' <span class="pill pill-orange">Porter/a</span>' : '')
        . ((int)$p['own'] !== 1 && $p['other_teams'] ? ' <small class="muted">' . e($p['other_teams']) . '</small>' : '') . '</span></label>';
};
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url('/partits/' . (int)$m['id'])) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Partit</a>
  <section class="panel take-head">
    <div>
      <h2 class="panel-title"><?= e($m['team_name']) ?> <?= $m['is_home'] ? 'vs' : '@' ?> <?= e($m['opponent']) ?></h2>
      <p class="muted small"><?= e(ucfirst(fdate($m['starts_at'], 'full'))) ?><?= $m['location'] ? ' · ' . e($m['location']) : '' ?></p>
    </div>
    <?php if ($sent): ?><span class="pill pill-green">Enviada</span><?php endif; ?>
  </section>

  <form method="post" action="<?= e(url('/partits/' . (int)$m['id'] . '/convocatoria')) ?>" class="form">
    <?= csrf_field() ?>
    <section class="panel">
      <div class="panel-head"><h2 class="panel-title">Plantilla</h2><span class="small muted" data-pick-count></span></div>
      <div class="pick-list"><?php foreach ($own as $p) { echo $row($p); } ?></div>
      <?php if (!$own): ?><p class="muted">Aquest equip no té jugadors/es.</p><?php endif; ?>
    </section>
    <?php if ($others): ?>
      <section class="panel">
        <div class="panel-head"><h2 class="panel-title">Pugen de categoria <small class="muted">(categories inferiors)</small></h2></div>
        <div class="pick-list"><?php foreach ($others as $p) { echo $row($p); } ?></div>
      </section>
    <?php endif; ?>
    <section class="panel form">
      <div class="field-row">
        <label class="field"><span>Hora de citació <small class="muted">(opcional)</small></span>
          <input type="time" name="meeting_time" value="<?= e($callup && $callup['meeting_at'] ? substr((string)$callup['meeting_at'], 11, 5) : '') ?>" step="300"></label>
      </div>
      <label class="field"><span>Missatge per a les jugadores <small class="muted">(opcional)</small></span>
        <textarea name="message" rows="3" maxlength="2000" placeholder="Ex.: Cal portar l'equipació blanca."><?= e($callup ? (string)$callup['message'] : '') ?></textarea></label>
      <div class="form-actions">
        <?php if ($sent): ?>
          <button class="btn btn-primary" type="submit" name="send" value="0"><?= icon('check', 'icon icon-sm') ?> Desa els canvis</button>
        <?php else: ?>
          <button class="btn btn-primary" type="submit" name="send" value="1"><?= icon('send', 'icon icon-sm') ?> Envia la convocatòria</button>
          <button class="btn" type="submit" name="send" value="0">Desa com a esborrany</button>
        <?php endif; ?>
      </div>
      <?php if ($sent): ?><p class="hint small">Les noves jugadores que afegeixis la rebran com a pendent. Les respostes de les altres es mantenen.</p><?php endif; ?>
    </section>
  </form>
  <?php if ($callup): ?>
    <form method="post" action="<?= e(url('/partits/' . (int)$m['id'] . '/convocatoria/eliminar')) ?>" data-confirm="Segur que vols eliminar la convocatòria i totes les respostes?" class="right">
      <?= csrf_field() ?>
      <button class="btn btn-danger btn-sm" type="submit">Elimina la convocatòria</button>
    </form>
  <?php endif; ?>
</div>
