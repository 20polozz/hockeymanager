<?php
/** @var string $tab @var array $seasons @var int $season @var array $matches @var array $record @var array $teams @var int $team @var bool $canManage */
use App\Repositories\MatchRepository as M;
$q = array_filter(['equip' => $team ?: null, 'temporada' => $season ?: null]);
?>
<nav class="tabs" aria-label="Partits">
  <a href="<?= e(url('/partits', $q)) ?>" class="<?= $tab === 'propers' ? 'is-active' : '' ?>"><?= icon('calendar', 'icon icon-sm') ?> Propers</a>
  <a href="<?= e(url('/partits', $q + ['vista' => 'disputats'])) ?>" class="<?= $tab === 'disputats' ? 'is-active' : '' ?>"><?= icon('trophy', 'icon icon-sm') ?> Disputats i resultats</a>
</nav>
<?= component('team_chips', ['teams' => $teams, 'active' => $team, 'path' => '/partits', 'query' => array_filter(['vista' => $tab === 'disputats' ? 'disputats' : null, 'temporada' => $season ?: null])]) ?>

<div class="page-head">
  <form method="get" action="<?= e(url('/partits')) ?>" class="inline-search">
    <?php if ($tab === 'disputats'): ?><input type="hidden" name="vista" value="disputats"><?php endif; ?>
    <?php if ($team): ?><input type="hidden" name="equip" value="<?= (int)$team ?>"><?php endif; ?>
    <label class="visually-hidden" for="season">Temporada</label>
    <select id="season" name="temporada" class="select-sm" data-autosubmit>
      <option value="0"<?= $season === 0 ? ' selected' : '' ?>>Totes les temporades</option>
      <?php foreach ($seasons as $s): ?>
        <option value="<?= (int)$s['id'] ?>"<?= $season === (int)$s['id'] ? ' selected' : '' ?>>Temporada <?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="btn" type="submit">Aplica</button></noscript>
  </form>
  <?php if ($canManage): ?>
    <a class="btn btn-primary" href="<?= e(url('/partits/nou', $team ? ['equip' => $team] : [])) ?>"><?= icon('plus', 'icon icon-sm') ?> Nou partit</a>
  <?php endif; ?>
</div>

<?php if ($tab === 'disputats' && $matches): $pj = count($matches); ?>
  <section class="record">
    <div><strong><?= $pj ?></strong><span>Partits</span></div>
    <div class="ok"><strong><?= $record['V'] ?></strong><span>Victòries</span></div>
    <div><strong><?= $record['E'] ?></strong><span>Empats</span></div>
    <div class="ko"><strong><?= $record['D'] ?></strong><span>Derrotes</span></div>
    <div><strong><?= $record['gf'] ?>–<?= $record['ga'] ?></strong><span>Gols (favor–contra)</span></div>
  </section>
<?php endif; ?>

<?php if (!$matches): ?>
  <section class="panel"><?= component('empty_state', ['icon' => 'trophy', 'text' => $tab === 'disputats' ? 'Encara no hi ha partits disputats en aquesta temporada.' : 'No hi ha partits programats.']) ?></section>
<?php else: ?>
  <ul class="match-list">
    <?php foreach ($matches as $m):
        $played = $m['status'] === 'jugat';
        $gf = (int)$m['goals_for']; $ga = (int)$m['goals_against'];
        [$stLabel, $stTone] = M::STATUSES[$m['status']]; ?>
      <li>
        <a class="match-row" href="<?= e(url('/partits/' . (int)$m['id'])) ?>">
          <span class="date-chip">
            <span class="date-chip-month"><?= e(fdate($m['starts_at'], 'monthshort')) ?></span>
            <span class="date-chip-day"><?= e(fdate($m['starts_at'], 'daynum')) ?></span>
          </span>
          <span class="match-body">
            <span class="match-teams"><span class="team-dot" style="--team: <?= e($m['color']) ?>"></span><?= e($m['team_name']) ?> <span class="muted"><?= $m['is_home'] ? 'vs' : '@' ?></span> <?= e($m['opponent']) ?></span>
            <span class="event-meta"><?= e(fdate($m['starts_at'], 'short')) ?> · <?= e(fdate($m['starts_at'], 'time')) ?><?= $m['competition'] ? ' · ' . e($m['competition']) : '' ?> · <?= $m['is_home'] ? 'Local' : 'Visitant' ?></span>
          </span>
          <?php if ($played): ?>
            <span class="result-badge tone-<?= $gf > $ga ? 'green' : ($gf < $ga ? 'red' : 'muted') ?>"><?= $gf > $ga ? 'V' : ($gf < $ga ? 'D' : 'E') ?></span>
            <span class="result-score"><?= $gf ?> – <?= $ga ?></span>
          <?php else: ?>
            <?php if ($m['status'] !== 'programat'): ?><span class="pill pill-<?= $stTone ?>"><?= e($stLabel) ?></span><?php endif; ?>
            <?php if ($m['callup_id']): ?>
              <span class="pill <?= $m['sent_at'] ? 'pill-green' : 'pill-muted' ?>" title="Convocatòria"><?= icon('clipboard', 'icon icon-xs') ?> <?= $m['sent_at'] ? (int)$m['confirmed'] . '/' . (int)$m['called'] : 'Esborrany' ?></span>
            <?php endif; ?>
          <?php endif; ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
