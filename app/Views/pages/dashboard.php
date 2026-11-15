<?php
/**
 * @var bool $isPlayer @var bool $hasTeams @var int $totalPlayers
 * @var array $attendance @var array|null $nextTraining @var array|null $nextMatch
 * @var array $quickCards @var array $upcoming @var array $results @var array $activity
 * @var array $attendanceTeams @var array $pendingCallups
 */
use App\Core\Auth;
use App\Core\Gate;

$user = Auth::user();
$activityIcons = ['attendance' => ['checklist', 'green'], 'callup' => ['clipboard', 'violet'], 'match' => ['trophy', 'amber'], 'exercise' => ['cone', 'orange'], 'system' => ['settings', 'navy']];
?>
<section class="welcome">
  <div>
    <h2 class="welcome-title"><?= e(greeting()) ?>, <?= e(first_name((string)$user['name'])) ?>!</h2>
    <p class="welcome-text">Benvingut a HockeyManager. Aquí tens el resum de la teva activitat esportiva.</p>
  </div>
  <div class="welcome-date"><?= icon('calendar', 'icon icon-sm') ?> <?= e(ucfirst(fdate(date('Y-m-d'), 'day'))) ?></div>
</section>

<?php if (!$hasTeams): ?>
  <div class="panel">
    <?= component('empty_state', [
        'icon' => 'shield',
        'text' => Auth::isManager()
            ? 'Encara no hi ha cap equip a la temporada actual. Quan es creïn equips (mòdul Equips), aquí veuràs el resum del club.'
            : 'Encara no tens cap equip assignat. Parla amb el coordinador del club.',
    ]) ?>
  </div>
<?php endif; ?>

<section class="stats-grid" aria-label="Resum">
  <?= component('stat_card', [
      'icon' => 'users', 'tone' => 'navy',
      'label' => $isPlayer ? 'Jugadors/es al teu equip' : 'Total de jugadors/es',
      'value' => (string)$totalPlayers,
      'hint' => $isPlayer ? null : 'Jugadors/es actius',
      'href' => Gate::allows('players.view') ? url('/jugadors') : null,
  ]) ?>
  <?= component('stat_card', [
      'icon' => 'percent', 'tone' => 'green',
      'label' => $isPlayer ? 'La teva assistència' : 'Assistència',
      'value' => $attendance['percent'] === null ? '—' : $attendance['percent'] . '%',
      'hint' => $attendance['percent'] === null ? 'Sense dades encara' : 'Últims 30 dies' . (!empty($attendance['sessions']) ? ' · ' . $attendance['sessions'] . ' sessions' : ''),
      'href' => Gate::allows('attendance.view') ? url('/assistencia') : null,
  ]) ?>
  <?= component('stat_card', [
      'icon' => 'dumbbell', 'tone' => 'blue',
      'label' => 'Pròxim entrenament',
      'value' => $nextTraining ? ucfirst(fdate($nextTraining['starts_at'], 'short')) : '—',
      'hint' => $nextTraining ? fdate($nextTraining['starts_at'], 'time') . ' · ' . $nextTraining['team_name'] : 'Cap entrenament programat',
      'href' => url('/calendari'),
  ]) ?>
  <?= component('stat_card', [
      'icon' => 'trophy', 'tone' => 'orange',
      'label' => 'Pròxim partit',
      'value' => $nextMatch ? ($nextMatch['is_home'] ? 'vs ' : '@ ') . $nextMatch['opponent'] : '—',
      'hint' => $nextMatch ? ucfirst(fdate($nextMatch['starts_at'], 'short')) . ' ' . fdate($nextMatch['starts_at'], 'time') . ' · ' . $nextMatch['team_name'] : 'Cap partit programat',
      'href' => url('/partits'),
  ]) ?>
</section>

<section aria-labelledby="quick-title">
  <h2 id="quick-title" class="section-title">Accés ràpid</h2>
  <div class="quick-grid">
    <?php foreach ($quickCards as $card): ?>
      <?= component('quick_card', $card) ?>
    <?php endforeach; ?>
  </div>
</section>

<div class="dash-grid">
  <section class="panel">
    <?= component('panel_head', ['title' => 'Pròxims esdeveniments', 'href' => url('/calendari'), 'linkText' => 'Calendari']) ?>
    <?php if ($upcoming): ?>
      <ul class="event-list">
        <?php foreach ($upcoming as $ev): ?><?= component('event_item', ['event' => $ev]) ?><?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'calendar', 'text' => 'No hi ha esdeveniments programats.']) ?>
    <?php endif; ?>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Convocatòries pendents', 'href' => url('/convocatories'), 'linkText' => 'Convocatòries']) ?>
    <?php if ($pendingCallups): ?>
      <ul class="callup-list">
        <?php foreach ($pendingCallups as $c): ?>
          <li class="callup-item">
            <div class="callup-top">
              <span class="team-dot" style="--team: <?= e($c['color']) ?>"></span>
              <strong><?= e($c['team_name']) ?> <?= $c['is_home'] ? 'vs' : '@' ?> <?= e($c['opponent']) ?></strong>
              <span class="muted"><?= e(fdate($c['starts_at'], 'short')) ?></span>
            </div>
            <?php if (isset($c['total'])):
                $total = max(1, (int)$c['total']); ?>
              <div class="stack-bar" role="img" aria-label="<?= (int)$c['confirmed'] ?> confirmats, <?= (int)$c['declined'] ?> rebutjats, <?= (int)$c['pending'] ?> pendents">
                <span class="seg seg-green" style="width: <?= round((int)$c['confirmed'] / $total * 100, 1) ?>%"></span>
                <span class="seg seg-red" style="width: <?= round((int)$c['declined'] / $total * 100, 1) ?>%"></span>
                <span class="seg seg-muted" style="width: <?= round((int)$c['pending'] / $total * 100, 1) ?>%"></span>
              </div>
              <div class="legend">
                <span><i class="dot dot-green"></i><?= (int)$c['confirmed'] ?> confirmats</span>
                <span><i class="dot dot-red"></i><?= (int)$c['declined'] ?> rebutjats</span>
                <span><i class="dot dot-muted"></i><?= (int)$c['pending'] ?> pendents</span>
              </div>
            <?php else: ?>
              <p class="muted small">
                <?= icon('pin', 'icon icon-xs') ?> <?= e($c['location'] ?: 'Pista per confirmar') ?>
                <?php if (!empty($c['meeting_at'])): ?> · Citació <?= e(fdate($c['meeting_at'], 'time')) ?><?php endif; ?>
              </p>
              <a class="btn btn-sm btn-primary" href="<?= e(url('/convocatories')) ?>">Respon la convocatòria</a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'clipboard', 'text' => 'No hi ha cap resposta pendent.']) ?>
    <?php endif; ?>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Últims resultats', 'href' => url('/partits'), 'linkText' => 'Partits']) ?>
    <?php if ($results): ?>
      <ul class="result-list">
        <?php foreach ($results as $r):
            $gf = (int)$r['goals_for'];
            $ga = (int)$r['goals_against'];
            [$label, $tone] = $gf > $ga ? ['V', 'green'] : ($gf < $ga ? ['D', 'red'] : ['E', 'muted']); ?>
          <li class="result-item">
            <span class="result-badge tone-<?= $tone ?>" title="<?= $gf > $ga ? 'Victòria' : ($gf < $ga ? 'Derrota' : 'Empat') ?>"><?= $label ?></span>
            <span class="result-body">
              <span class="result-title"><?= $r['is_home'] ? 'vs' : '@' ?> <?= e($r['opponent']) ?></span>
              <span class="result-meta"><span class="team-dot" style="--team: <?= e($r['color']) ?>"></span><?= e($r['team_name']) ?> · <?= e(fdate($r['starts_at'], 'short')) ?></span>
            </span>
            <span class="result-score"><?= $gf ?> – <?= $ga ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <?= component('empty_state', ['icon' => 'trophy', 'text' => 'Encara no hi ha partits disputats.']) ?>
    <?php endif; ?>
  </section>

  <?php if (!$isPlayer): ?>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Assistència per equip', 'href' => url('/assistencia'), 'linkText' => 'Assistència']) ?>
      <?php
        $withData = array_filter($attendanceTeams, function ($t) { return $t['percent'] !== null; });
        if ($withData): ?>
        <p class="muted small">Últims 30 dies · presents i retards sobre el total</p>
        <ul class="bar-list">
          <?php foreach ($attendanceTeams as $t): ?>
            <li>
              <span class="bar-label"><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><?= e($t['name']) ?></span>
              <span class="bar-track"><span class="bar-fill <?= $t['percent'] !== null && $t['percent'] < 75 ? 'bar-warn' : '' ?>" style="width: <?= (int)$t['percent'] ?>%"></span></span>
              <span class="bar-value"><?= $t['percent'] === null ? '—' : (int)$t['percent'] . '%' ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <?= component('empty_state', ['icon' => 'checklist', 'text' => 'Encara no s\'ha passat llista aquest mes.']) ?>
      <?php endif; ?>
    </section>

    <section class="panel panel-wide">
      <?= component('panel_head', ['title' => 'Últimes activitats']) ?>
      <?php if ($activity): ?>
        <ul class="activity-list">
          <?php foreach ($activity as $a):
              [$aIcon, $aTone] = $activityIcons[$a['type']] ?? ['activity', 'muted']; ?>
            <li class="activity-item">
              <span class="tile tile-sm tone-<?= e($aTone) ?>"><?= icon($aIcon, 'icon icon-sm') ?></span>
              <span class="activity-body">
                <span><strong><?= e($a['user_name'] ?? 'Sistema') ?></strong> <?= e(mb_strtolower(mb_substr($a['description'], 0, 1)) . mb_substr($a['description'], 1)) ?></span>
                <span class="muted small"><?= e(ago($a['created_at'])) ?><?= $a['team_name'] ? ' · ' . e($a['team_name']) : '' ?></span>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <?= component('empty_state', ['icon' => 'activity', 'text' => 'Encara no hi ha activitat registrada.']) ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>
