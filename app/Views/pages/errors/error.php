<?php
/** @var int $code @var string|null $detail */
$titles = [403 => 'No tens permís', 404 => 'Pàgina no trobada', 405 => 'Mètode no permès', 419 => 'Sessió caducada', 500 => 'Alguna cosa ha fallat'];
$texts = [
    403 => 'El teu rol no té accés a aquesta secció.',
    404 => 'La pàgina que busques no existeix o no hi tens accés.',
    405 => 'Aquesta adreça no accepta aquest tipus de petició.',
    419 => 'Torna a carregar la pàgina i prova-ho de nou.',
    500 => 'S\'ha produït un error inesperat. Si continua passant, avisa el coordinador.',
];
?>
<div class="auth-card error-card">
  <span class="error-code"><?= (int)$code ?></span>
  <h1 class="auth-title"><?= e($titles[$code] ?? 'Error') ?></h1>
  <p class="muted"><?= e($code === 419 && $detail ? $detail : ($texts[$code] ?? '')) ?></p>
  <?php if ($detail && $code !== 419 && ($code !== 500 || App\Core\Config::get('app.env') === 'development')): ?>
    <pre class="error-detail"><?= e($detail) ?></pre>
  <?php endif; ?>
  <a class="btn btn-primary btn-block" href="<?= e(url('/')) ?>">Torna a l'inici</a>
</div>
