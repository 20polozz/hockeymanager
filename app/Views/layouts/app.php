<?php
/**
 * Layout principal: sidebar + barra superior + contingut.
 * @var string $content
 * @var string $pageTitle
 */
use App\Core\Config;
use App\Repositories\Settings;

$appName = (string)Config::get('app.name', 'HockeyManager');
?><!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b1a36">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($pageTitle ?? '') ?> · <?= e($appName) ?></title>
  <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <script>try{if(localStorage.getItem('hm-sidebar')==='collapsed'){document.documentElement.classList.add('sidebar-collapsed');}}catch(e){}</script>
</head>
<body>
<a class="skip-link" href="#main">Salta al contingut</a>
<div class="app">
  <?= App\Core\View::partial('partials/sidebar', ['activeNav' => $activeNav ?? '', 'appName' => $appName]) ?>
  <div class="sidebar-backdrop" data-sidebar-close></div>

  <div class="main">
    <?= App\Core\View::partial('partials/topbar', ['pageTitle' => $pageTitle ?? '', 'showTeamPicker' => !empty($showTeamPicker)]) ?>

    <main id="main" class="content" tabindex="-1">
      <?php if (Settings::hasDemoData()): ?>
        <div class="demo-banner" role="note">
          <?= icon('info', 'icon icon-sm') ?>
          <span><strong>Dades de demostració.</strong> Els equips, jugadors i resultats que veus són fictícis i serveixen per provar l'aplicació.</span>
        </div>
      <?php endif; ?>
      <?= App\Core\View::partial('partials/flash') ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
