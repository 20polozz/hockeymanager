<?php
/** @var array|null $user @var array $defaults @var array $roles @var array $teams @var array $players */
use App\Core\Auth;
$isEdit = $user !== null;
$v = function (string $k) use ($user, $defaults): string { return form_val($user, $k, $defaults[$k] ?? ''); };
$role = $v('role') ?: 'entrenador';
$oldTeams = old_array('teams');
$oldRoles = old_array('staff_role');
$staff = $user['staff'] ?? [];
$checked = $oldTeams !== null ? array_map('intval', $oldTeams) : array_keys($staff);
$descs = ['admin' => 'Tot, inclòs el manteniment de la base de dades', 'coordinador' => 'Gestiona tot el club: equips, jugadors/es, usuaris', 'entrenador' => 'Els seus equips: llista, partits, convocatòries, exercicis', 'jugador' => 'El seu perfil, calendari, convocatòries i estadístiques'];
?>
<div class="form-page">
  <a class="back-link" href="<?= e(url('/usuaris')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Usuaris</a>
  <section class="panel">
    <form class="form" method="post" action="<?= e(url($isEdit ? '/usuaris/' . (int)$user['id'] : '/usuaris')) ?>" autocomplete="off">
      <?= csrf_field() ?>
      <div class="field-row">
        <label class="field"><span>Nom i cognoms</span>
          <input type="text" name="name" value="<?= e($v('name')) ?>" required maxlength="120" autofocus></label>
        <label class="field"><span>Nom d'usuari <small class="muted">(per entrar)</small></span>
          <input type="text" name="username" value="<?= e($v('username')) ?>" required maxlength="50" pattern="[a-zA-Z0-9._\-]{3,50}" autocapitalize="none" spellcheck="false" placeholder="ex.: albert.arnau"></label>
      </div>
      <div class="field-row">
        <label class="field"><span>Correu <small class="muted">(opcional, també serveix per entrar)</small></span>
          <input type="email" name="email" value="<?= e($v('email')) ?>" maxlength="190"></label>
        <label class="field"><span><?= $isEdit ? 'Nova contrasenya <small class="muted">(deixa-ho buit per no canviar-la)</small>' : 'Contrasenya <small class="muted">(mínim 8)</small>' ?></span>
          <input type="text" name="password" value="" minlength="8"<?= $isEdit ? '' : ' required' ?> autocomplete="new-password"></label>
      </div>

      <fieldset class="field">
        <legend>Rol</legend>
        <div class="role-cards">
          <?php foreach ($roles as $r): ?>
            <label class="role-card"><input type="radio" name="role" value="<?= e($r) ?>"<?= $role === $r ? ' checked' : '' ?> data-role<?= $isEdit && (int)$user['id'] === Auth::id() && $r !== $user['role'] ? ' disabled' : '' ?>>
              <span><strong><?= e(Auth::roleLabels()[$r]) ?></strong><small><?= e($descs[$r]) ?></small></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="field" data-role-panel="entrenador"<?= $role === 'entrenador' ? '' : ' hidden' ?>>
        <legend>Equips que entrena</legend>
        <div class="team-checks">
          <?php foreach ($teams as $t): $tid = (int)$t['id']; $on = in_array($tid, $checked, true); $sr = $oldRoles !== null ? ($oldRoles[$tid] ?? 'principal') : ($staff[$tid] ?? 'principal'); ?>
            <div class="team-check<?= $on ? ' is-on' : '' ?>">
              <label class="team-check-main"><input type="checkbox" name="teams[]" value="<?= $tid ?>"<?= $on ? ' checked' : '' ?> data-toggle-row><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><span><?= e($t['name']) ?></span></label>
              <select name="staff_role[<?= $tid ?>]" class="select-xs" aria-label="Rol a <?= e($t['name']) ?>">
                <option value="principal"<?= $sr === 'principal' ? ' selected' : '' ?>>Principal</option>
                <option value="ajudant"<?= $sr === 'ajudant' ? ' selected' : '' ?>>Ajudant</option>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <label class="field" data-role-panel="jugador"<?= $role === 'jugador' ? '' : ' hidden' ?>><span>Fitxa de jugador/a vinculada</span>
        <select name="player_id">
          <option value="0">Sense vincular</option>
          <?php foreach ($players as $p): ?>
            <option value="<?= (int)$p['id'] ?>"<?= $v('player_id') === (string)$p['id'] ? ' selected' : '' ?>><?= e($p['first_name'] . ' ' . $p['last_name']) ?><?= $p['teams'] ? ' · ' . e($p['teams']) : '' ?></option>
          <?php endforeach; ?>
        </select>
        <small class="muted">Així veurà les seves convocatòries, el seu perfil i les seves estadístiques.</small></label>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $isEdit ? 'Desa els canvis' : 'Crea el compte' ?></button>
        <a class="btn" href="<?= e(url('/usuaris')) ?>">Cancel·la</a>
      </div>
    </form>
  </section>
</div>
