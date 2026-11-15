<div class="auth-card">
  <h1 class="auth-title">Inicia sessió</h1>
  <p class="muted">Accedeix amb el compte que t'ha donat el club.</p>
  <form class="form" method="post" action="<?= e(url('/login')) ?>" novalidate>
    <?= csrf_field() ?>
    <label class="field"><span>Usuari o correu electrònic</span>
      <input type="text" name="login" value="<?= e(old('login')) ?>" required maxlength="190" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus></label>
    <label class="field"><span>Contrasenya</span>
      <input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block" type="submit">Entra</button>
  </form>
</div>
