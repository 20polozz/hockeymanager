<?php
/** @var array $m @var array|null $callup @var array $sheet @var bool $canManage @var bool $canCallup */
use App\Repositories\CallupRepository as C;
use App\Repositories\MatchRepository as M;
$played = $m['status'] === 'jugat';
$gf = (int)$m['goals_for']; $ga = (int)$m['goals_against'];
[$stLabel, $stTone] = M::STATUSES[$m['status']];
$future = strtotime((string)$m['starts_at']) > time();
$scorers = array_filter($sheet, function ($r) { return (int)$r['goals'] > 0 || (int)$r['assists'] > 0; });
$cards = array_filter($sheet, function ($r) { return (int)$r['blue_cards'] > 0 || (int)$r['red_cards'] > 0; });
$gks = array_filter($sheet, function ($r) { return $r['position'] === 'porter' && ($r['gk_saves'] !== null || $r['gk_goals_against'] !== null); });
?>
<div class="form-page form-page-wide">
  <a class="back-link" href="<?= e(url('/partits', $played ? ['vista' => 'disputats'] : [])) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Partits</a>

  <section class="panel scoreboard">
    <div class="sb-meta">
      <span class="pill pill-<?= $stTone ?>"><?= e($stLabel) ?></span>
      <?php if ($m['competition']): ?><span class="muted small"><?= e($m['competition']) ?></span><?php endif; ?>
    </div>
    <div class="sb-main">
      <div class="sb-team<?= $m['is_home'] ? ' own' : '' ?>"><?= $m['is_home'] ? '<span class="team-dot" style="--team: ' . e($m['color']) . '"></span>' . e($m['team_name']) : e($m['opponent']) ?></div>
      <div class="sb-score"><?php if ($played): ?><?= $m['is_home'] ? $gf : $ga ?> <span>–</span> <?= $m['is_home'] ? $ga : $gf ?><?php else: ?><span class="sb-time"><?= e(fdate($m['starts_at'], 'time')) ?></span><?php endif; ?></div>
      <div class="sb-team<?= !$m['is_home'] ? ' own' : '' ?>"><?= !$m['is_home'] ? '<span class="team-dot" style="--team: ' . e($m['color']) . '"></span>' . e($m['team_name']) : e($m['opponent']) ?></div>
    </div>
    <ul class="meta-list sb-info">
      <li><?= icon('calendar', 'icon icon-sm') ?> <?= e(ucfirst(fdate($m['starts_at'], 'day'))) ?> · <?= e(fdate($m['starts_at'], 'time')) ?></li>
      <?php if ($m['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <?= e($m['location']) ?></li><?php endif; ?>
      <li><?= icon('flag', 'icon icon-sm') ?> Temporada <?= e($m['season']) ?></li>
    </ul>
  </section>

  <div class="dash-grid">
    <?php if ($canCallup): ?>
      <section class="panel">
        <?= component('panel_head', ['title' => 'Convocatòria']) ?>
        <?php if (!$callup): ?>
          <p class="muted">Encara no s'ha fet la convocatòria.</p>
          <?php if ($future): ?><a class="btn btn-primary" href="<?= e(url('/partits/' . (int)$m['id'] . '/convocatoria')) ?>"><?= icon('clipboard', 'icon icon-sm') ?> Fes la convocatòria</a><?php endif; ?>
        <?php else: $cn = $callup['counts']; $tot = max(1, count($callup['players'])); ?>
          <?php if (!$callup['sent_at']): ?><p><span class="pill pill-muted">Esborrany</span> <span class="muted small">Encara no s'ha enviat a les jugadores.</span></p><?php endif; ?>
          <div class="stack-bar">
            <span class="seg seg-green" style="width: <?= round($cn['confirmat'] / $tot * 100, 1) ?>%"></span>
            <span class="seg seg-red" style="width: <?= round($cn['rebutjat'] / $tot * 100, 1) ?>%"></span>
            <span class="seg seg-muted" style="width: <?= round($cn['pendent'] / $tot * 100, 1) ?>%"></span>
          </div>
          <div class="legend"><span><i class="dot dot-green"></i><?= $cn['confirmat'] ?> confirmades</span><span><i class="dot dot-red"></i><?= $cn['rebutjat'] ?> rebutjades</span><span><i class="dot dot-muted"></i><?= $cn['pendent'] ?> pendents</span></div>
          <ul class="callup-people">
            <?php foreach ($callup['players'] as $p): [$rl, $rt] = C::RESPONSES[$p['response']]; ?>
              <li><span><?= e($p['first_name'] . ' ' . $p['last_name']) ?><?= $p['position'] === 'porter' ? ' <small class="muted">(P)</small>' : '' ?><?php if ($p['response_note']): ?><small class="muted"> — <?= e($p['response_note']) ?></small><?php endif; ?></span><span class="pill pill-<?= $rt ?>"><?= $rl ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($callup['meeting_at']): ?><p class="small muted">Citació a les <?= e(fdate($callup['meeting_at'], 'time')) ?></p><?php endif; ?>
          <?php if ($future): ?><a class="btn" href="<?= e(url('/partits/' . (int)$m['id'] . '/convocatoria')) ?>"><?= icon('wrench', 'icon icon-sm') ?> <?= $callup['sent_at'] ? 'Modifica la convocatòria' : 'Revisa i envia' ?></a><?php endif; ?>
        <?php endif; ?>
        <p class="hint small">La confirmació és la resposta a la convocatòria. Qui juga de veritat s'indica a l'acta.</p>
      </section>
    <?php endif; ?>

    <section class="panel">
      <?= component('panel_head', ['title' => 'Acta del partit']) ?>
      <?php if (!$sheet): ?>
        <p class="muted">Encara no s'ha omplert l'acta (qui ha jugat, gols, assistències, targetes i dades de porteria).</p>
      <?php else: ?>
        <p class="small muted"><?= count($sheet) ?> jugadors/es han jugat</p>
        <?php if ($scorers): ?>
          <h3 class="sub-title">Gols i assistències</h3>
          <ul class="att-history">
            <?php foreach ($scorers as $r): ?>
              <li><span><?= e($r['first_name'] . ' ' . $r['last_name']) ?></span><span class="small"><?= (int)$r['goals'] ? '<strong>' . (int)$r['goals'] . '</strong> ' . ((int)$r['goals'] === 1 ? 'gol' : 'gols') : '' ?><?= (int)$r['goals'] && (int)$r['assists'] ? ' · ' : '' ?><?= (int)$r['assists'] ? (int)$r['assists'] . ' ' . ((int)$r['assists'] === 1 ? 'assistència' : 'assistències') : '' ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if ($cards): ?>
          <h3 class="sub-title">Targetes</h3>
          <ul class="att-history">
            <?php foreach ($cards as $r): ?>
              <li><span><?= e($r['first_name'] . ' ' . $r['last_name']) ?></span><span><?= str_repeat('<i class="card card-blue" title="Blava"></i>', (int)$r['blue_cards']) ?><?= str_repeat('<i class="card card-red" title="Vermella"></i>', (int)$r['red_cards']) ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if ($gks): ?>
          <h3 class="sub-title">Porteria</h3>
          <ul class="att-history">
            <?php foreach ($gks as $r): $sv = $r['gk_saves']; $gaa = $r['gk_goals_against']; ?>
              <li><span><?= e($r['first_name'] . ' ' . $r['last_name']) ?></span><span class="small"><?= $sv !== null ? (int)$sv . ' aturades' : '' ?><?= $gaa !== null ? ' · ' . (int)$gaa . ' gols encaixats' : '' ?><?= ($sv !== null && $gaa !== null && ($sv + $gaa) > 0) ? ' · <strong>' . round($sv / ($sv + $gaa) * 100) . '%</strong>' : '' ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($canManage): ?>
        <a class="btn<?= $sheet ? '' : ' btn-primary' ?>" href="<?= e(url('/partits/' . (int)$m['id'] . '/acta')) ?>"><?= icon('clipboard', 'icon icon-sm') ?> <?= $sheet ? 'Edita l\'acta' : 'Omple l\'acta' ?></a>
      <?php endif; ?>
    </section>
  </div>

  <?php if ($canManage): ?>
    <section class="panel danger-zone">
      <a class="btn" href="<?= e(url('/partits/' . (int)$m['id'] . '/editar')) ?>"><?= icon('wrench', 'icon icon-sm') ?> <?= $played ? 'Edita' : 'Edita / posa el resultat' ?></a>
      <form method="post" action="<?= e(url('/partits/' . (int)$m['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar aquest partit? També s'esborraran la convocatòria i l'acta.">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"><?= icon('x', 'icon icon-sm') ?> Elimina</button>
      </form>
    </section>
  <?php endif; ?>
</div>
