<?php
/** @var array $teams @var int $team @var array $rows */
?>
<?= component('team_chips', ['teams' => $teams, 'active' => $team, 'path' => '/convocatories']) ?>
<p class="hint small">Aquí tens els propers partits. Fes la convocatòria, envia-la i segueix qui confirma. Pots convocar també jugadors/es de categories inferiors.</p>
<?php if (!$rows): ?>
  <section class="panel"><?= component('empty_state', ['icon' => 'clipboard', 'text' => 'No hi ha partits programats. Crea\'ls a l\'apartat Partits.']) ?>
    <p class="center"><a class="btn btn-primary" href="<?= e(url('/partits/nou')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nou partit</a></p></section>
<?php else: ?>
  <ul class="match-list">
    <?php foreach ($rows as $r): $tot = max(1, (int)$r['total']); ?>
      <li>
        <div class="match-row callup-row">
          <span class="date-chip">
            <span class="date-chip-month"><?= e(fdate($r['starts_at'], 'monthshort')) ?></span>
            <span class="date-chip-day"><?= e(fdate($r['starts_at'], 'daynum')) ?></span>
          </span>
          <a class="match-body" href="<?= e(url('/partits/' . (int)$r['match_id'])) ?>">
            <span class="match-teams"><span class="team-dot" style="--team: <?= e($r['color']) ?>"></span><?= e($r['team_name']) ?> <span class="muted"><?= $r['is_home'] ? 'vs' : '@' ?></span> <?= e($r['opponent']) ?></span>
            <span class="event-meta"><?= e(fdate($r['starts_at'], 'short')) ?> · <?= e(fdate($r['starts_at'], 'time')) ?></span>
          </a>
          <span class="callup-state">
            <?php if (!$r['callup_id']): ?>
              <a class="btn btn-sm btn-primary" href="<?= e(url('/partits/' . (int)$r['match_id'] . '/convocatoria')) ?>">Convoca</a>
            <?php elseif (!$r['sent_at']): ?>
              <span class="pill pill-muted">Esborrany</span>
              <a class="btn btn-sm" href="<?= e(url('/partits/' . (int)$r['match_id'] . '/convocatoria')) ?>">Envia</a>
            <?php else: ?>
              <span class="mini-stack" title="<?= (int)$r['confirmed'] ?> confirmades, <?= (int)$r['declined'] ?> rebutjades, <?= (int)$r['pending'] ?> pendents">
                <span class="stack-bar"><span class="seg seg-green" style="width: <?= round($r['confirmed'] / $tot * 100, 1) ?>%"></span><span class="seg seg-red" style="width: <?= round($r['declined'] / $tot * 100, 1) ?>%"></span><span class="seg seg-muted" style="width: <?= round($r['pending'] / $tot * 100, 1) ?>%"></span></span>
                <span class="small"><strong><?= (int)$r['confirmed'] ?></strong>/<?= (int)$r['total'] ?> confirmades<?= (int)$r['pending'] ? ' · ' . (int)$r['pending'] . ' pendents' : '' ?></span>
              </span>
            <?php endif; ?>
          </span>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
