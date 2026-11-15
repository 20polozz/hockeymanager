<?php
/** @var array $players @var string $search */
?>
<div class="page-head">
  <p class="muted"><?= count($players) ?> jugadors<?= $search !== '' ? ' per a «' . e($search) . '»' : '' ?></p>
  <form class="inline-search" method="get" action="<?= e(url('/jugadors')) ?>">
    <label class="visually-hidden" for="player-search">Cerca jugadors</label>
    <input id="player-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Filtra per nom…" maxlength="80">
    <button class="btn" type="submit"><?= icon('search', 'icon icon-sm') ?> Filtra</button>
  </form>
</div>

<section class="panel panel-flush">
  <?php if ($players): ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th scope="col">Dorsal</th><th scope="col">Jugador</th><th scope="col">Posició</th><th scope="col">Equip</th></tr></thead>
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
              <td><span class="pill <?= $p['position'] === 'porter' ? 'pill-orange' : 'pill-blue' ?>"><?= $p['position'] === 'porter' ? 'Porter' : 'Jugador' ?></span></td>
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
    <?= component('empty_state', ['icon' => 'users', 'text' => $search !== '' ? 'Cap jugador coincideix amb la cerca.' : 'Encara no hi ha jugadors en aquests equips.']) ?>
  <?php endif; ?>
</section>
