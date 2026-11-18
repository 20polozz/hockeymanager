<?php
/** @var string $role @var array $users */
use App\Core\Auth;
$labels = Auth::roleLabels();
$tones = ['admin' => 'red', 'coordinador' => 'navy', 'entrenador' => 'blue', 'jugador' => 'green'];
?>
<div class="page-head">
  <nav class="filter-chips" aria-label="Filtra per rol">
    <a class="chip<?= $role === '' ? ' is-active' : '' ?>" href="<?= e(url('/usuaris')) ?>">Tots</a>
    <?php foreach ($labels as $k => $l): ?><a class="chip<?= $role === $k ? ' is-active' : '' ?>" href="<?= e(url('/usuaris', ['rol' => $k])) ?>"><?= e($l) ?></a><?php endforeach; ?>
  </nav>
  <a class="btn btn-primary" href="<?= e(url('/usuaris/nou', $role ? ['rol' => $role] : [])) ?>"><?= icon('plus', 'icon icon-sm') ?> Nou usuari</a>
</div>
<ul class="m-cards">
  <?php foreach ($users as $u): $canEdit = $u['role'] !== 'admin' || Auth::hasRole(Auth::ROLE_ADMIN); ?>
    <li><<?= $canEdit ? 'a href="' . e(url('/usuaris/' . (int)$u['id'] . '/editar')) . '"' : 'div' ?> class="m-card<?= (int)$u['active'] ? '' : ' row-off' ?>">
      <span class="avatar"><?= e(initials((string)$u['name'])) ?></span>
      <span class="m-card-body"><strong><?= e($u['name']) ?></strong>
        <span class="small muted"><?= e($u['username'] ?: (string)$u['email']) ?><?= (int)$u['active'] ? '' : ' · desactivat' ?></span></span>
      <span class="pill pill-<?= $tones[$u['role']] ?? 'muted' ?>"><?= e($labels[$u['role']] ?? $u['role']) ?></span>
    </<?= $canEdit ? 'a' : 'div' ?>></li>
  <?php endforeach; ?>
</ul>
<section class="panel panel-flush desktop-only">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Nom</th><th>Usuari</th><th>Rol</th><th>Equips / fitxa</th><th>Últim accés</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): $canEdit = $u['role'] !== 'admin' || Auth::hasRole(Auth::ROLE_ADMIN); ?>
          <tr class="<?= (int)$u['active'] ? '' : 'row-off' ?>">
            <td><span class="person"><span class="avatar avatar-sm"><?= e(initials((string)$u['name'])) ?></span><?= e($u['name']) ?></span><?= (int)$u['active'] ? '' : ' <span class="pill pill-muted">Desactivat</span>' ?></td>
            <td><code><?= e($u['username'] ?: $u['email']) ?></code></td>
            <td><span class="pill pill-<?= $tones[$u['role']] ?? 'muted' ?>"><?= e($labels[$u['role']] ?? $u['role']) ?></span></td>
            <td class="wrap">
              <?php if ($u['role'] === 'entrenador'): ?>
                <?php foreach ($u['teams'] as $t): ?><span class="team-tag"><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><?= e($t['name']) ?></span> <?php endforeach; ?>
                <?= $u['teams'] ? '' : '<span class="muted small">Sense equips</span>' ?>
              <?php elseif ($u['role'] === 'jugador'): ?>
                <?= $u['player_name'] ? e($u['player_name']) : '<span class="muted small">Sense fitxa vinculada</span>' ?>
              <?php else: ?><span class="muted small">Tot el club</span><?php endif; ?>
            </td>
            <td class="small muted"><?= $u['last_login_at'] ? e(ago($u['last_login_at'])) : 'Mai' ?></td>
            <td class="row-actions">
              <?php if ($canEdit): ?>
                <a class="btn btn-sm" href="<?= e(url('/usuaris/' . (int)$u['id'] . '/editar')) ?>">Edita</a>
                <?php if ((int)$u['id'] !== Auth::id()): ?>
                  <form method="post" action="<?= e(url('/usuaris/' . (int)$u['id'] . '/estat')) ?>" data-confirm="<?= (int)$u['active'] ? 'Segur que vols desactivar el compte de ' . e($u['name']) . '?' : 'Reactivar el compte de ' . e($u['name']) . '?' ?>">
                    <?= csrf_field() ?><button class="btn btn-sm<?= (int)$u['active'] ? ' btn-danger' : '' ?>" type="submit"><?= (int)$u['active'] ? 'Desactiva' : 'Reactiva' ?></button>
                  </form>
                  <form method="post" action="<?= e(url('/usuaris/' . (int)$u['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar definitivament el compte de <?= e($u['name']) ?>? No es pot desfer. (La fitxa de jugador/a i l'historial es conserven.)">
                    <?= csrf_field() ?><button class="btn-trash" type="submit" aria-label="Elimina el compte de <?= e($u['name']) ?>" title="Elimina el compte"><?= icon('trash', 'icon icon-sm') ?></button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
