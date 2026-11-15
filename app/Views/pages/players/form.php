<?php
/** @var array|null $player @var array $memberships @var array $teams */
$isEdit = $player !== null;
$val = function (string $key) use ($player): string {
    $old = old($key, "\0");
    if ($old !== "\0") {
        return $old;
    }
    return $player !== null && isset($player[$key]) ? (string)$player[$key] : '';
};
// Equips marcats i dorsals (dades antigues del formulari si hi ha hagut un error)
$oldTeams = old_array('teams');
$oldDorsals = old_array('dorsal');
$checked = $oldTeams !== null ? array_map('intval', $oldTeams) : array_keys($memberships);
$dorsalOf = function (int $tid) use ($oldDorsals, $memberships): string {
    if ($oldDorsals !== null) {
        return (string)($oldDorsals[$tid] ?? '');
    }
    return isset($memberships[$tid]) ? (string)$memberships[$tid] : '';
};
$position = $val('position') ?: 'jugador';
$back = $isEdit ? '/jugadors/' . (int)$player['id'] : '/jugadors';
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url($back)) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> <?= $isEdit ? e($player['first_name'] . ' ' . $player['last_name']) : 'Jugadors/es' ?></a>

  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/jugadors/' . (int)$player['id'] : '/jugadors')) ?>">
      <?= csrf_field() ?>
      <?= App\Core\View::partial('pages/players/_fields', ['player' => $player, 'memberships' => $memberships, 'teams' => $teams, 'autofocus' => true]) ?>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Afegeix a la plantilla' ?></button>
        <a class="btn" href="<?= e(url($back)) ?>">Cancel·la</a>
      </div>
    </form>
  </section>

  <?php if ($isEdit): ?>
    <section class="panel danger-zone">
      <div>
        <h2 class="panel-title">Donar de baixa</h2>
        <p class="muted small">Deixarà d'aparèixer a la plantilla, però es conserva el seu historial d'assistència i estadístiques.</p>
      </div>
      <form method="post" action="<?= e(url('/jugadors/' . (int)$player['id'] . '/baixa')) ?>" data-confirm="Segur que vols donar de baixa <?= e($player['first_name'] . ' ' . $player['last_name']) ?>?">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit">Dona de baixa</button>
      </form>
    </section>
  <?php endif; ?>
</div>
