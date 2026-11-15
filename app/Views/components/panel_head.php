<?php
/**
 * Capçalera d'un panell amb enllaç opcional.
 * @var string $title  @var string|null $href  @var string|null $linkText
 */
?>
<div class="panel-head">
  <h2 class="panel-title"><?= e($title) ?></h2>
  <?php if (!empty($href)): ?>
    <a class="panel-link" href="<?= e($href) ?>"><?= e($linkText ?? 'Veure-ho tot') ?> <?= icon('chevron-right', 'icon icon-xs') ?></a>
  <?php endif; ?>
</div>
