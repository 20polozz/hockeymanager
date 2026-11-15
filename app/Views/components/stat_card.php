<?php
/**
 * Targeta de xifra resumida.
 * @var string $icon  @var string $tone  @var string $label  @var string $value
 * @var string|null $hint  @var string|null $href
 */
$tag = !empty($href) ? 'a' : 'div';
?>
<<?= $tag ?> class="stat-card"<?= !empty($href) ? ' href="' . e($href) . '"' : '' ?>>
  <span class="tile tone-<?= e($tone) ?>"><?= icon($icon) ?></span>
  <span class="stat-body">
    <span class="stat-label"><?= e($label) ?></span>
    <span class="stat-value"><?= e($value) ?></span>
    <?php if (!empty($hint)): ?><span class="stat-hint"><?= e($hint) ?></span><?php endif; ?>
  </span>
</<?= $tag ?>>
