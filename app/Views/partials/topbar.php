<?php
/**
 * Barra superior: títol, cercador, notificacions i perfil.
 * @var string $pageTitle
 */
use App\Core\Auth;
use App\Core\Gate;

$user = Auth::user();

$notifications = [];
?>
<header class="topbar">
  <button type="button" class="icon-btn menu-btn" data-sidebar-open aria-label="Obre el menú" aria-controls="sidebar"><?= icon('menu') ?></button>
  <h1 class="page-title"><?= e($pageTitle) ?></h1>


  <div class="topbar-actions">

    <details class="dropdown">
      <summary class="icon-btn" aria-label="Notificacions (<?= count($notifications) ?>)">
        <?= icon('bell') ?>
        <?php if ($notifications): ?><span class="badge-dot"><?= count($notifications) ?></span><?php endif; ?>
      </summary>
      <div class="dropdown-menu dropdown-wide">
        <div class="dropdown-head">Notificacions</div>
        <?php if (!$notifications): ?>
          <p class="dropdown-empty">No tens res pendent.</p>
        <?php else: foreach ($notifications as $n): ?>
          <a class="notif" href="<?= e($n['href']) ?>">
            <span class="tile tile-sm tone-<?= e($n['tone']) ?>"><?= icon($n['icon'], 'icon icon-sm') ?></span>
            <span><strong><?= e($n['title']) ?></strong><small><?= e($n['text']) ?></small></span>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </details>

    <?php if ($user): ?>
      <details class="dropdown">
        <summary class="profile-btn" aria-label="Menú del perfil">
          <span class="avatar"><?= e(initials((string)$user['name'])) ?></span>
          <span class="profile-text">
            <span class="profile-name"><?= e(first_name((string)$user['name'])) ?></span>
            <span class="profile-role"><?= e(Auth::roleLabels()[$user['role']] ?? '') ?></span>
          </span>
          <?= icon('chevron-down', 'icon icon-sm profile-caret') ?>
        </summary>
        <div class="dropdown-menu">
          <div class="dropdown-head">
            <strong><?= e($user['name']) ?></strong>
            <small><?= e($user['email'] ?: '@' . $user['username']) ?></small>
          </div>
          <?php if (Gate::allows('players.own')): ?>
            <a class="dropdown-item" href="<?= e(url('/perfil')) ?>"><?= icon('user', 'icon icon-sm') ?> El meu perfil</a>
          <?php endif; ?>
          <a class="dropdown-item" href="<?= e(url('/configuracio')) ?>"><?= icon('settings', 'icon icon-sm') ?> Configuració</a>
          <form action="<?= e(url('/logout')) ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="dropdown-item danger"><?= icon('logout', 'icon icon-sm') ?> Tanca la sessió</button>
          </form>
        </div>
      </details>
    <?php endif; ?>
  </div>
</header>
