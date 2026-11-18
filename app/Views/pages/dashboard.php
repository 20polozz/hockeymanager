<?php
/**
 * @var bool $isPlayer @var bool $hasTeams @var int $totalPlayers
 * @var array $attendance @var array|null $nextTraining
 * @var array $quickCards @var array $upcoming @var array $activity
 * @var array $attendanceTeams @var array $unavailable
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

    <section class="panel">
      <?= component('panel_head', ['title' => 'Jugadores no disponibles']) ?>
      <?php if ($unavailable): ?>
        <ul class="absence-list">
          <?php foreach ($unavailable as $a): ?>
            <li class="absence-item<?= (int)$a['is_current'] === 1 ? ' is-current' : ' is-upcoming' ?>">
              <a class="absence-main" href="<?= e(url('/jugadors/' . (int)$a['player_id'])) ?>#absencies">
                <strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong>
                <span class="pill <?= (int)$a['is_current'] === 1 ? 'pill-red' : 'pill-orange' ?>"><?= e(App\Repositories\AbsenceRepository::label($a)) ?></span>
                <span class="muted small"><?= e(App\Repositories\AbsenceRepository::period($a)) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <?= component('empty_state', ['icon' => 'users', 'text' => 'Totes les jugadores estan disponibles.']) ?>
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
