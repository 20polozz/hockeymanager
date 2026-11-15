<?php
/**
 * Layout sense menú (login, instal·lació, errors).
 * @var string $content
 */
use App\Core\Config;

$appName = (string)Config::get('app.name', 'HockeyManager');
?><!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0b1a36">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($pageTitle ?? 'HockeyManager') ?> · <?= e($appName) ?></title>
  <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-body">
  <main class="auth-shell">
    <div class="auth-brand">
      <span class="brand-mark"><?= icon('logo') ?></span>
      <span class="brand-name"><?= e($appName) ?></span>
    </div>
    <?= App\Core\View::partial('partials/flash') ?>
    <?= $content ?>
    <p class="auth-foot">Gestió esportiva per a clubs d'hoquei patins</p>
  </main>
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
