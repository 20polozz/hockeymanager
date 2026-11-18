<?php
/**
 * @var string $activeNav
 * @var string $appName
 */
use App\Core\Auth;
use App\Core\Gate;
use App\Repositories\Settings;

$nav = require HM_ROOT . '/app/navigation.php';
$user = Auth::user();
$club = Settings::get('club_name', (string)App\Core\Config::get('app.club_name', ''));
?>
<aside class="sidebar" id="sidebar" aria-label="Menú principal">
  <div class="sidebar-head">
    <a class="brand" href="<?= e(url('/')) ?>">
      <span class="brand-mark"><?= icon('logo') ?></span>
      <span class="brand-text">
        <span class="brand-name"><?= e($appName) ?></span>
        <?php if ($club !== ''): ?><span class="brand-club"><?= e($club) ?></span><?php endif; ?>
      </span>
    </a>
    <button type="button" class="icon-btn sidebar-close" data-sidebar-close aria-label="Tanca el menú"><?= icon('x') ?></button>
  </div>

  <nav class="nav">
    <ul>
      <?php foreach ($nav as $item):
          if (!Gate::allows($item['can'])) {
              continue;
          }
          $active = $activeNav === $item['key']; ?>
        <li>
          <a href="<?= e(url($item['path'])) ?>" class="nav-link<?= $active ? ' is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?> data-tooltip="<?= e($item['label']) ?>">
            <?= icon($item['icon']) ?>
            <span class="nav-label"><?= e($item['label']) ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="sidebar-foot">
    <?php if ($user): ?>
      <a class="sidebar-user<?= ($activeNav ?? '') === 'settings' ? ' is-active' : '' ?>" href="<?= e(url('/configuracio')) ?>" title="El meu compte i configuració">
        <span class="avatar avatar-sm"><?= e(initials((string)$user['name'])) ?></span>
        <span class="sidebar-user-text">
          <span class="sidebar-user-name"><?= e($user['name']) ?></span>
          <span class="sidebar-user-role"><?= e(Auth::roleLabels()[$user['role']] ?? '') ?></span>
        </span>
        <?= icon('settings', 'icon icon-sm sidebar-user-cog') ?>
      </a>
    <?php endif; ?>
    <button type="button" class="nav-link collapse-btn" data-sidebar-collapse aria-label="Replega el menú" data-tooltip="Desplega el menú">
      <?= icon('chevrons-left') ?>
      <span class="nav-label">Replega el menú</span>
    </button>
  </div>
</aside>
