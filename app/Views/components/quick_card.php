<?php
/**
 * Targeta d'accés ràpid a un mòdul.
 * @var string $path  @var string $icon  @var string $tone  @var string $title
 * @var string $text  @var string|null $meta
 */
?>
<a class="quick-card tone-border-<?= e($tone) ?>" href="<?= e(url($path)) ?>">
  <span class="tile tile-lg tone-<?= e($tone) ?>"><?= icon($icon) ?></span>
  <span class="quick-title"><?= e($title) ?></span>
  <span class="quick-text"><?= e($text) ?></span>
  <?php if (!empty($meta)): ?><span class="quick-meta"><?= e($meta) ?></span><?php endif; ?>
  <span class="quick-arrow" aria-hidden="true"><?= icon('arrow-right', 'icon icon-sm') ?></span>
</a>
