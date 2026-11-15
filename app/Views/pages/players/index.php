<?php
/** @var array $players @var string $search @var array $teams @var int $activeTeam */
$q = $search !== '' ? ['q' => $search] : [];
$canManage = App\Core\Gate::allows('players.manage');
?>
<?= component('team_chips', ['teams' => $teams, 'active' => $activeTeam, 'path' => '/jugadors', 'query' => $q]) ?>

<div class="page-head">
  <p class="muted"><?= count($players) ?> jugadors/es<?= $search !== '' ? ' per a «' . e($search) . '»' : '' ?></p>
  <form class="inline-search" method="get" action="<?= e(url('/jugadors')) ?>">
    <?php if ($activeTeam > 0): ?><input type="hidden" name="equip" value="<?= (int)$activeTeam ?>"><?php endif; ?>
    <label class="visually-hidden" for="player-search">Cerca jugadors/es</label>
    <input id="player-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Filtra per nom…" maxlength="80">
    <button class="btn" type="submit"><?= icon('search', 'icon icon-sm') ?> Filtra</button>
  </form>
  <?php if ($canManage): ?>
    <a class="btn btn-primary" href="<?= e(url('/jugadors/nou', $activeTeam > 0 ? ['equip' => $activeTeam] : [])) ?>"><?= icon('plus', 'icon icon-sm') ?> Nou jugador/a</a>
  <?php endif; ?>
</div>

<section class="panel panel-flush">
  <?php if ($players): ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th scope="col">Dorsal</th><th scope="col">Jugador/a</th><th scope="col">Posició</th><th scope="col">Equip</th></tr></thead>
        <tbody>
          <?php foreach ($players as $p): ?>
            <tr>
              <td><span class="dorsal"><?= $p['dorsal'] !== null ? (int)$p['dorsal'] : '–' ?></span></td>
              <td>
                <a class="person" href="<?= e(url('/jugadors/' . (int)$p['id'])) ?>">
                  <span class="avatar avatar-sm"><?= e(initials($p['first_name'] . ' ' . $p['last_name'])) ?></span>
                  <span><?= e($p['first_name'] . ' ' . $p['last_name']) ?></span>
                </a>
              </td>
              <td><span class="pill <?= $p['position'] === 'porter' ? 'pill-orange' : 'pill-blue' ?>"><?= $p['position'] === 'porter' ? 'Porter/a' : 'Jugador/a' ?></span></td>
              <td>
                <span class="team-list">
                  <?php foreach ($p['teams'] as $t): ?>
                    <span class="team-tag"><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><?= e($t['name']) ?></span>
                  <?php endforeach; ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <?= component('empty_state', ['icon' => 'users', 'text' => $search !== '' ? 'Cap jugador/a coincideix amb la cerca.' : 'Encara no hi ha jugadors/es en aquests equips.']) ?>
  <?php endif; ?>
</section>
