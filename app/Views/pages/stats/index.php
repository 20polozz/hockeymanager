<?php
/**
 * @var array $seasons @var int $season @var array $teams @var int $team @var bool $isPlayer
 * @var array $summary @var array $results @var array $players @var array $goalkeepers @var array $attendance
 */
$q = array_filter(['temporada' => $season ?: null]);
$tot = ['played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'gf' => 0, 'ga' => 0];
foreach ($summary as $s) { foreach ($tot as $k => $_) { $tot[$k] += (int)$s[$k]; } }
$scorers = array_slice(array_values(array_filter($players, function ($p) { return (int)$p['goals'] > 0; })), 0, 5);
$assisters = $players;
usort($assisters, function ($a, $b) { return (int)$b['assists'] <=> (int)$a['assists']; });
$assisters = array_slice(array_values(array_filter($assisters, function ($p) { return (int)$p['assists'] > 0; })), 0, 5);
$maxG = $scorers ? max(array_map(function ($p) { return (int)$p['goals']; }, $scorers)) : 1;
$maxA = $assisters ? max(array_map(function ($p) { return (int)$p['assists']; }, $assisters)) : 1;
$maxR = 1;
foreach ($results as $r) { $maxR = max($maxR, (int)$r['goals_for'], (int)$r['goals_against']); }
$pct = function ($v): string { return $v === null ? '—' : rtrim(rtrim(number_format((float)$v, 1, ',', ''), '0'), ',') . '%'; };
?>
<?= component('team_chips', ['teams' => $teams, 'active' => $team, 'path' => '/estadistiques', 'query' => $q]) ?>
<div class="page-head">
  <form method="get" action="<?= e(url('/estadistiques')) ?>">
    <?php if ($team): ?><input type="hidden" name="equip" value="<?= (int)$team ?>"><?php endif; ?>
    <label class="visually-hidden" for="season">Temporada</label>
    <select id="season" name="temporada" class="select-sm" data-autosubmit>
      <option value="0"<?= $season === 0 ? ' selected' : '' ?>>Totes les temporades</option>
      <?php foreach ($seasons as $s): ?><option value="<?= (int)$s['id'] ?>"<?= $season === (int)$s['id'] ? ' selected' : '' ?>>Temporada <?= e($s['name']) ?></option><?php endforeach; ?>
    </select>
    <noscript><button class="btn" type="submit">Aplica</button></noscript>
  </form>
  <p class="muted small">Calculat a partir dels resultats, les actes i les llistes registrades.</p>
</div>

<section class="stats-grid" aria-label="Resum">
  <?= component('stat_card', ['icon' => 'trophy', 'tone' => 'navy', 'label' => 'Partits disputats', 'value' => (string)$tot['played'], 'hint' => $tot['won'] . 'V · ' . $tot['drawn'] . 'E · ' . $tot['lost'] . 'D']) ?>
  <?= component('stat_card', ['icon' => 'percent', 'tone' => 'green', 'label' => 'Percentatge de victòries', 'value' => $tot['played'] ? round($tot['won'] / $tot['played'] * 100) . '%' : '—', 'hint' => null]) ?>
  <?= component('stat_card', ['icon' => 'flag', 'tone' => 'blue', 'label' => 'Gols a favor', 'value' => (string)$tot['gf'], 'hint' => $tot['played'] ? number_format($tot['gf'] / $tot['played'], 1, ',', '') . ' per partit' : null]) ?>
  <?= component('stat_card', ['icon' => 'shield', 'tone' => 'red', 'label' => 'Gols en contra', 'value' => (string)$tot['ga'], 'hint' => $tot['played'] ? number_format($tot['ga'] / $tot['played'], 1, ',', '') . ' per partit' : null]) ?>
</section>

<div class="dash-grid">
  <section class="panel">
    <?= component('panel_head', ['title' => 'Per equip']) ?>
    <?php if (array_sum(array_column($summary, 'played')) === 0): ?>
      <?= component('empty_state', ['icon' => 'trophy', 'text' => 'Encara no hi ha partits disputats en aquest període.']) ?>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table table-compact">
          <thead><tr><th scope="col">Equip</th><th class="num">PJ</th><th class="num">V</th><th class="num">E</th><th class="num">D</th><th class="num">GF</th><th class="num">GC</th><th>Ratxa</th></tr></thead>
          <tbody>
            <?php foreach ($summary as $s): ?>
              <tr>
                <td><span class="team-dot" style="--team: <?= e($s['color']) ?>"></span><?= e($s['name']) ?></td>
                <td class="num"><?= (int)$s['played'] ?></td><td class="num"><?= (int)$s['won'] ?></td><td class="num"><?= (int)$s['drawn'] ?></td><td class="num"><?= (int)$s['lost'] ?></td>
                <td class="num"><?= (int)$s['gf'] ?></td><td class="num"><?= (int)$s['ga'] ?></td>
                <td><span class="form-strip"><?php foreach ($s['form'] as $f): ?><i class="f-<?= $f ?>"><?= $f ?></i><?php endforeach; ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Evolució (últims partits)']) ?>
    <?php if (!$results): ?>
      <?= component('empty_state', ['icon' => 'chart', 'text' => 'Sense resultats per mostrar.']) ?>
    <?php else: ?>
      <div class="evo" role="img" aria-label="Gols a favor i en contra dels últims partits">
        <?php foreach ($results as $r): $gf = (int)$r['goals_for']; $ga = (int)$r['goals_against']; ?>
          <div class="evo-col" title="<?= e(fdate($r['starts_at'], 'short') . ' · ' . $r['team_name'] . ' ' . ($r['is_home'] ? 'vs' : '@') . ' ' . $r['opponent'] . ': ' . $gf . '-' . $ga) ?>">
            <div class="evo-bars">
              <span class="evo-gf" style="height: <?= round($gf / $maxR * 100) ?>%"><b><?= $gf ?></b></span>
              <span class="evo-ga" style="height: <?= round($ga / $maxR * 100) ?>%"><b><?= $ga ?></b></span>
            </div>
            <span class="evo-res f-<?= $gf > $ga ? 'V' : ($gf < $ga ? 'D' : 'E') ?>"><?= $gf > $ga ? 'V' : ($gf < $ga ? 'D' : 'E') ?></span>
            <span class="evo-label"><?= e(fdate($r['starts_at'], 'daynum') . '/' . date('n', strtotime((string)$r['starts_at']))) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="legend"><span><i class="dot dot-green"></i>Gols a favor</span><span><i class="dot dot-red"></i>Gols en contra</span></div>
    <?php endif; ?>
  </section>

  <?php if (!$isPlayer): ?>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Màximes golejadores']) ?>
      <?php if (!$scorers): ?><?= component('empty_state', ['icon' => 'flag', 'text' => 'Encara no hi ha gols a les actes.']) ?><?php else: ?>
        <ul class="bar-list">
          <?php foreach ($scorers as $p): ?>
            <li><a class="bar-label" href="<?= e(url('/jugadors/' . (int)$p['id'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
              <span class="bar-track"><span class="bar-fill bar-blue" style="width: <?= round((int)$p['goals'] / $maxG * 100) ?>%"></span></span>
              <span class="bar-value"><?= (int)$p['goals'] ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Màximes assistents']) ?>
      <?php if (!$assisters): ?><?= component('empty_state', ['icon' => 'users', 'text' => 'Encara no hi ha assistències a les actes.']) ?><?php else: ?>
        <ul class="bar-list">
          <?php foreach ($assisters as $p): ?>
            <li><a class="bar-label" href="<?= e(url('/jugadors/' . (int)$p['id'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
              <span class="bar-track"><span class="bar-fill bar-violet" style="width: <?= round((int)$p['assists'] / $maxA * 100) ?>%"></span></span>
              <span class="bar-value"><?= (int)$p['assists'] ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

<section class="panel panel-flush stats-table-panel">
  <div class="panel-head panel-pad"><h2 class="panel-title"><?= $isPlayer ? 'Les meves estadístiques' : 'Jugadors/es' ?></h2></div>
  <?php if (!$players): ?>
    <?= component('empty_state', ['icon' => 'users', 'text' => 'Encara no hi ha actes de partit en aquest període.']) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th scope="col">Jugador/a</th><th class="num" title="Partits jugats">PJ</th><th class="num">Gols</th><th class="num">Assist.</th><th class="num" title="Gols + assistències">Punts</th><th class="num" title="Gols per partit">G/PJ</th><th class="num">T. blaves</th><th class="num">T. vermelles</th><th class="num" title="Assistència als entrenaments">Entrenaments</th></tr></thead>
        <tbody>
          <?php foreach ($players as $p): $att = $attendance[(int)$p['id']] ?? null; ?>
            <tr>
              <td><a class="person" href="<?= e(url($isPlayer ? '/perfil' : '/jugadors/' . (int)$p['id'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a><?= $p['position'] === 'porter' ? ' <span class="pill pill-orange">P</span>' : '' ?></td>
              <td class="num"><?= (int)$p['played'] ?></td>
              <td class="num"><strong><?= (int)$p['goals'] ?></strong></td>
              <td class="num"><?= (int)$p['assists'] ?></td>
              <td class="num"><?= (int)$p['goals'] + (int)$p['assists'] ?></td>
              <td class="num"><?= (int)$p['played'] ? number_format((int)$p['goals'] / (int)$p['played'], 2, ',', '') : '—' ?></td>
              <td class="num"><?= (int)$p['blue_cards'] ?></td>
              <td class="num"><?= (int)$p['red_cards'] ?></td>
              <td class="num"><?= $att === null ? '—' : $att . '%' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="panel panel-flush stats-table-panel">
  <div class="panel-head panel-pad"><h2 class="panel-title">Porteria</h2></div>
  <?php if (!$goalkeepers): ?>
    <?= component('empty_state', ['icon' => 'shield', 'text' => 'Encara no hi ha dades de porteria a les actes.']) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th scope="col">Porter/a</th><th class="num">PJ</th><th class="num">Gols encaixats</th><th class="num" title="Mitjana per partit amb dades">GE/PJ</th><th class="num">Aturades</th><th class="num">Llançaments rebuts</th><th class="num">% aturades</th><th class="num">Penals (aturats/afrontats)</th><th class="num">Faltes directes (aturades/afrontades)</th></tr></thead>
        <tbody>
          <?php foreach ($goalkeepers as $g): ?>
            <tr>
              <td><?= e($g['first_name'] . ' ' . $g['last_name']) ?></td>
              <td class="num"><?= (int)$g['played'] ?></td>
              <td class="num"><?= $g['goals_against'] === null ? '—' : (int)$g['goals_against'] ?></td>
              <td class="num"><?= $g['gaa'] === null ? '—' : number_format((float)$g['gaa'], 2, ',', '') ?></td>
              <td class="num"><?= $g['saves'] === null ? '—' : (int)$g['saves'] ?></td>
              <td class="num"><?= $g['shots'] === null ? '—' : (int)$g['shots'] ?></td>
              <td class="num"><strong><?= $pct($g['save_pct']) ?></strong></td>
              <td class="num"><?= $g['pen_faced'] === null ? '—' : (int)$g['pen_saved'] . '/' . (int)$g['pen_faced'] ?></td>
              <td class="num"><?= $g['fd_faced'] === null ? '—' : (int)$g['fd_saved'] . '/' . (int)$g['fd_faced'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="hint small panel-hint">El % d'aturades es calcula amb els partits on hi ha anotades aturades i gols encaixats.</p>
  <?php endif; ?>
</section>
