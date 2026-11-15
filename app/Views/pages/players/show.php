<?php
/** @var array $player @var array $history @var array $attendance @var array $attHistory @var bool $canManage */
$stLabels = ['present' => ['Present', 'green'], 'retard' => ['Retard', 'blue'], 'justificat' => ['Justificada', 'orange'], 'absent' => ['Absent', 'red']];
$name = $player['first_name'] . ' ' . $player['last_name'];
$age = $player['birth_date'] ? (int)date_diff(date_create($player['birth_date']), date_create('today'))->y : null;
$current = array_values(array_filter($history, function ($h) { return $h['left_at'] === null; }));
$totalAtt = array_sum($attendance);
$attended = $attendance['present'] + $attendance['retard'];
?>
<section class="panel profile-hero">
  <span class="avatar avatar-xl"><?= e(initials($name)) ?></span>
  <div class="profile-hero-body">
    <h2 class="module-title"><?= e($name) ?><?php if ((int)$player['active'] !== 1): ?> <span class="pill pill-red">Baixa</span><?php endif; ?></h2>
    <div class="chips">
      <span class="pill <?= $player['position'] === 'porter' ? 'pill-orange' : 'pill-blue' ?>"><?= $player['position'] === 'porter' ? 'Porter/a' : 'Jugador/a de camp' ?></span>
      <?php foreach ($current as $c): ?>
        <span class="pill pill-muted"><span class="team-dot" style="--team: <?= e($c['color']) ?>"></span><?= e($c['team_name']) ?><?= $c['dorsal'] !== null ? ' · #' . (int)$c['dorsal'] : '' ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if (!empty($canManage)): ?>
    <a class="btn hero-action" href="<?= e(url('/jugadors/' . (int)$player['id'] . '/editar')) ?>"><?= icon('wrench', 'icon icon-sm') ?> Edita la fitxa</a>
  <?php endif; ?>
</section>

<div class="dash-grid">
  <section class="panel">
    <?= component('panel_head', ['title' => 'Dades personals']) ?>
    <dl class="data-list">
      <div><dt>Data de naixement</dt><dd><?= $player['birth_date'] ? e(date('d/m/Y', strtotime($player['birth_date']))) . ($age !== null ? ' (' . $age . ' anys)' : '') : '—' ?></dd></div>
      <div><dt>Categoria</dt><dd><?= $current ? e((string)$current[0]['category']) : '—' ?></dd></div>
      <div><dt>Dorsal</dt><dd><?= $current && $current[0]['dorsal'] !== null ? (int)$current[0]['dorsal'] : '—' ?></dd></div>
      <div><dt>Temporada</dt><dd><?= $current ? e($current[0]['season']) : '—' ?></dd></div>
    </dl>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Assistència als entrenaments']) ?>
    <?php if ($totalAtt > 0): ?>
      <p class="big-number"><?= (int)round($attended / $totalAtt * 100) ?>%<span class="muted small"> de <?= $totalAtt ?> sessions</span></p>
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

  <section class="panel">
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

  <section class="panel">
    <?= component('panel_head', ['title' => 'Estadístiques esportives']) ?>
    <?= component('empty_state', ['icon' => 'chart', 'text' => 'Les estadístiques de partit (gols, assistències, targetes' . ($player['position'] === 'porter' ? ', aturades' : '') . ') arribaran amb el mòdul d\'Estadístiques.']) ?>
  </section>
</div>
