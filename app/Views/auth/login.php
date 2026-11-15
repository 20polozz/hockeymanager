<div class="auth-card">
  <h1 class="auth-title">Inicia sessió</h1>
  <p class="muted">Accedeix amb el compte que t'ha donat el club.</p>
  <form class="form" method="post" action="<?= e(url('/login')) ?>" novalidate>
    <?= csrf_field() ?>
    <label class="field"><span>Correu electrònic</span>
      <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="username" autofocus></label>
    <label class="field"><span>Contrasenya</span>
      <input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block" type="submit">Entra</button>
  </form>
</div>
