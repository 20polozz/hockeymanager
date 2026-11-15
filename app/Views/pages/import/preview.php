<?php
/** @var array $rows */
$ok = array_filter($rows, function ($r) { return !$r['errors']; });
$new = array_filter($ok, function ($r) { return !$r['existing']; });
?>
<div class="form-page form-page-wide">
  <a class="back-link" href="<?= e(url('/configuracio/importacio')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Importació</a>
  <section class="panel take-head">
    <div>
      <h2 class="panel-title"><?= count($rows) ?> files llegides</h2>
      <p class="muted small"><?= count($new) ?> fitxes noves · <?= count($ok) - count($new) ?> ja existents (s'afegiran a l'equip si cal) · <?= count($rows) - count($ok) ?> amb errors (s'ometran)</p>
    </div>
    <form method="post" action="<?= e(url('/configuracio/importacio/confirmar')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-primary" type="submit"<?= $ok ? '' : ' disabled' ?>><?= icon('check', 'icon icon-sm') ?> Importa <?= count($ok) ?> files</button>
    </form>
  </section>
  <section class="panel panel-flush">
    <div class="table-wrap">
      <table class="table preview-table">
        <thead><tr><th class="num">Línia</th><th>Nom</th><th>Naixement</th><th>Posició</th><th>Equip</th><th class="num">Dorsal</th><th>Estat</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="num muted"><?= (int)$r['line'] ?></td>
              <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
              <td><?= $r['birth_date'] ? e(date('d/m/Y', strtotime($r['birth_date']))) : e($r['birth_raw'] ?: '—') ?></td>
              <td><?= $r['position'] === 'porter' ? 'Porter/a' : 'Jugador/a' ?></td>
              <td><?= e($r['team_name']) ?></td>
              <td class="num"><?= $r['dorsal'] ?? '—' ?></td>
              <td class="<?= $r['errors'] ? 'err' : '' ?>"><?= $r['errors'] ? e(implode(', ', $r['errors'])) : ($r['existing'] ? '<span class="pill pill-blue">Ja existeix</span>' : '<span class="pill pill-green">Nova</span>') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
