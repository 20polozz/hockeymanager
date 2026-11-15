<?php
/** @var array $user */
use App\Core\Auth;
use App\Core\Gate;
?>
<div class="dash-grid">
  <section class="panel">
    <?= component('panel_head', ['title' => 'El meu compte']) ?>
    <dl class="data-list">
      <div><dt>Nom</dt><dd><?= e($user['name']) ?></dd></div>
      <?php if (!empty($user['username'])): ?><div><dt>Usuari</dt><dd><?= e($user['username']) ?></dd></div><?php endif; ?>
      <div><dt>Correu</dt><dd><?= e($user['email'] ?: '—') ?></dd></div>
      <div><dt>Rol</dt><dd><?= e(Auth::roleLabels()[$user['role']] ?? '') ?></dd></div>
    </dl>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Canvia la contrasenya']) ?>
    <form class="form" method="post" action="<?= e(url('/configuracio/contrasenya')) ?>">
      <?= csrf_field() ?>
      <label class="field"><span>Contrasenya actual</span>
        <input type="password" name="current_password" required autocomplete="current-password"></label>
      <label class="field"><span>Nova contrasenya <small class="muted">(mínim 8 caràcters)</small></span>
        <input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
      <label class="field"><span>Repeteix la nova contrasenya</span>
        <input type="password" name="new_password_confirmation" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-primary" type="submit">Desa la contrasenya</button>
    </form>
  </section>

  <?php if (Gate::allows('users.manage') || Gate::allows('players.manage')): ?>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Gestió del club']) ?>
      <div class="settings-links">
        <?php if (Gate::allows('users.manage')): ?><a class="btn" href="<?= e(url('/usuaris')) ?>"><?= icon('lock', 'icon icon-sm') ?> Usuaris i accessos</a><?php endif; ?>
        <?php if (Gate::allows('teams.manage')): ?><a class="btn" href="<?= e(url('/equips')) ?>"><?= icon('shield', 'icon icon-sm') ?> Equips</a><?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

</div>
