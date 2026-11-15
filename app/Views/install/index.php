<?php
/**
 * @var string $mode  no-config | db-error | install | update
 * @var bool $keyConfigured @var array $pending
 */
?>
<div class="auth-card auth-card-wide">
<?php if ($mode === 'no-config'): ?>
  <h1 class="auth-title">Falta la configuració</h1>
  <ol class="steps">
    <li>Crea una base de dades MariaDB/MySQL nova per a HockeyManager (independent de la de HockeyFantasy).</li>
    <li>Copia <code>config.example.php</code> com a <code>config.php</code> dins la carpeta de l'aplicació i omple-hi les dades de connexió.</li>
    <li>Posa-hi una <code>install_key</code> llarga i torna a carregar aquesta pàgina.</li>
  </ol>

<?php elseif ($mode === 'db-error'): ?>
  <h1 class="auth-title">No es pot connectar a la base de dades</h1>
  <p class="muted">Revisa el servidor, el nom de la base de dades, l'usuari i la contrasenya de <code>config.php</code>. El detall de l'error s'ha guardat al registre d'errors del servidor.</p>

<?php elseif ($mode === 'install'): ?>
  <h1 class="auth-title">Instal·la HockeyManager</h1>
  <p class="muted">Es crearan les taules i el compte del primer administrador.</p>
  <?php if (!$keyConfigured): ?>
    <div class="alert alert-error"><?= icon('alert', 'icon icon-sm') ?><span>Abans de continuar, posa una <code>install_key</code> a <code>config.php</code>.</span></div>
  <?php else: ?>
    <form class="form" method="post" action="<?= e(url('/install')) ?>">
      <?= csrf_field() ?>
      <label class="field"><span>Clau d'instal·lació</span>
        <input type="password" name="install_key" required autocomplete="off"></label>
      <label class="field"><span>Nom del club</span>
        <input type="text" name="club_name" value="<?= e(old('club_name')) ?>" required maxlength="100"></label>
      <div class="field-row">
        <label class="field"><span>El teu nom</span>
          <input type="text" name="name" value="<?= e(old('name')) ?>" required maxlength="120" autocomplete="name"></label>
        <label class="field"><span>Correu electrònic</span>
          <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email"></label>
      </div>
      <div class="field-row">
        <label class="field"><span>Contrasenya <small class="muted">(mín. 10)</small></span>
          <input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
        <label class="field"><span>Repeteix-la</span>
          <input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password"></label>
      </div>
      <label class="check">
        <input type="checkbox" name="demo" value="1" checked>
        <span>Carrega <strong>dades de demostració</strong> (equips, jugadors i partits fictícis, marcats com a tals) per provar l'aplicació.</span>
      </label>
      <button class="btn btn-primary btn-block" type="submit">Instal·la</button>
    </form>
  <?php endif; ?>

<?php else: ?>
  <h1 class="auth-title">Actualització de la base de dades</h1>
  <?php if ($pending): ?>
    <p>Migracions pendents:</p>
    <ul class="check-list">
      <?php foreach ($pending as $m): ?><li><?= icon('database', 'icon icon-sm') ?> <?= e($m) ?></li><?php endforeach; ?>
    </ul>
    <form method="post" action="<?= e(url('/install')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-primary btn-block" type="submit">Aplica les migracions</button>
    </form>
  <?php else: ?>
    <p class="muted"><?= icon('check', 'icon icon-sm') ?> La base de dades està al dia.</p>
  <?php endif; ?>
  <a class="btn btn-block" href="<?= e(url('/')) ?>">Torna al Dashboard</a>
<?php endif; ?>
</div>
