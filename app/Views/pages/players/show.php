<?php
/** @var array $player @var array $history @var array $attendance @var array $attHistory @var bool $canManage @var array $absences @var bool $canAbsence @var array $memberships @var array $teams */
use App\Repositories\AbsenceRepository;
$isPlayerUser = App\Core\Auth::hasRole(App\Core\Auth::ROLE_PLAYER);
$currentAbs = array_values(array_filter($absences, static function ($a) { return (int)$a['is_current'] === 1; }));
$stLabels = ['present' => ['Present', 'green'], 'retard' => ['Retard', 'blue'], 'justificat' => ['Justificada', 'orange'], 'absent' => ['Absent', 'red']];
$name = $player['first_name'] . ' ' . $player['last_name'];
$age = $player['birth_date'] ? (int)date_diff(date_create($player['birth_date']), date_create('today'))->y : null;
$current = array_values(array_filter($history, function ($h) { return $h['left_at'] === null; }));
$totalAtt = array_sum($attendance);
$attended = $attendance['present'] + $attendance['retard'];
$counted = $totalAtt - $attendance['justificat'];
?>
<?php if (($activeNav ?? '') === 'players'): ?><a class="back-link" href="<?= e(url('/jugadors')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Jugadors/es</a><?php endif; ?>
<section class="panel profile-hero" id="player-hero">
  <span class="avatar avatar-xl"><?= e(initials($name)) ?></span>
  <div class="profile-hero-body">
    <h2 class="module-title"><?= e($name) ?><?php if ((int)$player['active'] !== 1): ?> <span class="pill pill-red">Baixa</span><?php endif; ?></h2>
    <div class="chips">
      <span class="pill <?= $player['position'] === 'porter' ? 'pill-orange' : 'pill-blue' ?>"><?= $player['position'] === 'porter' ? 'Porter/a' : 'Jugador/a de camp' ?></span>
      <?php foreach ($current as $c): ?>
        <span class="pill pill-muted"><span class="team-dot" style="--team: <?= e($c['color']) ?>"></span><?= e($c['team_name']) ?><?= $c['dorsal'] !== null ? ' · #' . (int)$c['dorsal'] : '' ?></span>
      <?php endforeach; ?>
      <?php foreach ($currentAbs as $a): ?>
        <span class="pill pill-red"><?= icon('alert', 'icon icon-xs') ?> No disponible · <?= e(AbsenceRepository::period($a)) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="dash-grid">
  <?php if (!empty($canManage)): ?>
    <section class="panel panel-wide" id="fitxa">
      <?= component('panel_head', ['title' => 'Fitxa']) ?>
      <form class="form" method="post" action="<?= e(url('/jugadors/' . (int)$player['id'])) ?>" data-live="player-hero,fitxa,historial">
        <?= csrf_field() ?>
        <?= App\Core\View::partial('pages/players/_fields', ['player' => $player, 'memberships' => $memberships, 'teams' => $teams]) ?>
        <?php if ($age !== null): ?><p class="muted small" style="margin:0"><?= $age ?> anys</p><?php endif; ?>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Desa els canvis</button></div>
      </form>
    </section>
  <?php else: ?>
    <section class="panel">
    <?= component('panel_head', ['title' => 'Dades personals']) ?>
    <dl class="data-list">
      <div><dt>Data de naixement</dt><dd><?= $player['birth_date'] ? e(date('d/m/Y', strtotime($player['birth_date']))) . ($age !== null ? ' (' . $age . ' anys)' : '') : '—' ?></dd></div>
      <div><dt>Categoria</dt><dd><?= $current ? e((string)$current[0]['category']) : '—' ?></dd></div>
      <div><dt>Dorsal</dt><dd><?= $current && $current[0]['dorsal'] !== null ? (int)$current[0]['dorsal'] : '—' ?></dd></div>
      <div><dt>Temporada</dt><dd><?= $current ? e($current[0]['season']) : '—' ?></dd></div>
    </dl>
  </section>
  <?php endif; ?>

  <section class="panel panel-wide" id="absencies">
    <?= component('panel_head', ['title' => 'Absències i baixes temporals']) ?>
    <?php if ($absences): ?>
      <ul class="absence-list">
        <?php foreach ($absences as $a):
            $past = (int)$a['is_past'] === 1; $cur = (int)$a['is_current'] === 1;
            $mine = !$isPlayerUser || (int)$a['created_by'] === App\Core\Auth::id(); ?>
          <li class="absence-item<?= $cur ? ' is-current' : ($past ? ' is-past' : ' is-upcoming') ?>">
            <div class="absence-main">
              <span class="pill <?= $cur ? 'pill-red' : ($past ? 'pill-muted' : 'pill-orange') ?>"><?= e(AbsenceRepository::label($a)) ?></span>
              <strong class="absence-dates"><?= e(date('d/m/Y', strtotime($a['starts_on']))) ?> → <?= $a['ends_on'] ? e(date('d/m/Y', strtotime($a['ends_on']))) : 'sense data de tornada' ?></strong>
              <span class="muted small"><?= $cur ? 'Ara no disponible' : ($past ? 'Acabada' : 'Pròximament') ?><?= $a['created_by_name'] ? ' · per ' . e($a['created_by_name']) : '' ?></span>
              <?php if ($a['note']): ?><p class="absence-note"><?= e($a['note']) ?></p><?php endif; ?>
            </div>
            <?php if ($canAbsence && $mine): ?>
              <div class="absence-actions">
                <?php if ($cur): ?>
                  <form method="post" action="<?= e(url('/absencies/' . (int)$a['id'] . '/tornada')) ?>"><?= csrf_field() ?>
                    <button class="btn btn-sm" type="submit"><?= icon('check', 'icon icon-sm') ?> Ja ha tornat</button></form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/absencies/' . (int)$a['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar aquesta absència?"><?= csrf_field() ?>
                  <button class="icon-btn" type="submit" aria-label="Elimina l'absència"><?= icon('x', 'icon icon-sm') ?></button></form>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">Cap absència registrada.</p>
    <?php endif; ?>

    <?php if ($canAbsence): ?>
      <details class="absence-add">
        <summary class="btn"><?= icon('plus', 'icon icon-sm') ?> <?= $isPlayerUser ? 'Avisa que no podràs venir' : 'Afegeix una absència' ?></summary>
        <form method="post" action="<?= e(url('/jugadors/' . (int)$player['id'] . '/absencies')) ?>" class="absence-form">
          <?= csrf_field() ?>
          <label class="field"><span>Motiu</span>
            <select name="type" required>
              <?php foreach (AbsenceRepository::TYPES as $k => $lbl): ?><option value="<?= e($k) ?>"><?= e($lbl) ?></option><?php endforeach; ?>
            </select></label>
          <div class="field-row">
            <label class="field"><span>Des del dia</span>
              <input type="date" name="starts_on" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label class="field" id="abs-ends"><span>Fins al dia <small class="muted">(últim dia que no hi és)</small></span>
              <input type="date" name="ends_on" required></label>
          </div>
          <label class="check check-inline"><input type="checkbox" name="open_ended" value="1" data-hide="abs-ends"><span>Sense data de tornada (encara no se sap)</span></label>
          <label class="field"><span>Nota <small class="muted">(opcional)</small></span>
            <input type="text" name="note" maxlength="255" placeholder="Ex.: esquinç de turmell, viatge d'estudis…"></label>
          <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Desa</button></div>
        </form>
      </details>
    <?php endif; ?>
  </section>


  <section class="panel">
    <?= component('panel_head', ['title' => 'Assistència als entrenaments']) ?>
    <?php if ($totalAtt > 0): ?>
      <p class="big-number"><?= $counted > 0 ? (int)round($attended / $counted * 100) . '%' : '—' ?><span class="muted small"> de <?= $totalAtt ?> sessions</span></p>
      <div class="legend legend-col">
        <span><i class="dot dot-green"></i><?= $attendance['present'] ?> presents</span>
        <span><i class="dot dot-blue"></i><?= $attendance['retard'] ?> retards</span>
        <span><i class="dot dot-orange"></i><?= $attendance['justificat'] ?> absències justificades</span>
        <span><i class="dot dot-red"></i><?= $attendance['absent'] ?> absències</span>
      </div>
      <?php if ($attHistory): ?>
        <h3 class="sub-title">Últimes sessions</h3>
        <ul class="att-history">
          <?php foreach ($attHistory as $h): [$lbl, $tone] = $stLabels[$h['status']] ?? ['—', 'muted']; ?>
            <li>
              <span class="small"><?= e(fdate($h['starts_at'], 'short')) ?> · <?= e($h['team_name']) ?></span>
              <span class="pill pill-<?= $tone ?>" title="<?= e((string)$h['observation']) ?>"><?= $lbl ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'checklist', 'text' => 'Encara no hi ha assistència registrada.']) ?>
    <?php endif; ?>
  </section>

  <section class="panel" id="historial">
    <?= component('panel_head', ['title' => 'Historial d\'equips']) ?>
    <ul class="timeline">
      <?php foreach ($history as $h): ?>
        <li>
          <span class="team-dot" style="--team: <?= e($h['color']) ?>"></span>
          <span><strong><?= e($h['team_name']) ?></strong> · <?= e($h['season']) ?><br>
            <span class="muted small">Des del <?= e(date('d/m/Y', strtotime($h['joined_on']))) ?><?= $h['left_at'] ? ' fins al ' . e(date('d/m/Y', strtotime($h['left_at']))) : ' · actual' ?></span></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

</div>

<?php if (!empty($canManage)): ?>
  <section class="panel danger-zone" style="margin-top:20px">
    <div>
      <h2 class="panel-title">Donar de baixa del club</h2>
      <p class="muted small">Deixarà d'aparèixer a la plantilla, però es conserva el seu historial d'assistència i estadístiques.</p>
    </div>
    <form method="post" action="<?= e(url('/jugadors/' . (int)$player['id'] . '/baixa')) ?>" data-confirm="Segur que vols donar de baixa <?= e($name) ?>?">
      <?= csrf_field() ?>
      <button class="btn btn-danger" type="submit">Dona de baixa</button>
    </form>
  </section>
<?php endif; ?>
