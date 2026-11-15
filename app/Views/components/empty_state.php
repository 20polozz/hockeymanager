<?php
/**
 * Estat buit dins d'un panell.
 * @var string $icon  @var string $text
 */
?>
<div class="empty">
  <span class="tile tone-muted"><?= icon($icon ?? 'info') ?></span>
  <p><?= e($text) ?></p>
</div>
