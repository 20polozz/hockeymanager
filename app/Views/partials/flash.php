<?php
$icons = ['success' => 'check', 'error' => 'alert', 'info' => 'info', 'warning' => 'alert'];
foreach (take_flashes() as $f):
    $type = isset($icons[$f['type']]) ? $f['type'] : 'info'; ?>
  <div class="alert alert-<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
    <?= icon($icons[$type], 'icon icon-sm') ?>
    <span><?= e($f['message']) ?></span>
    <button type="button" class="alert-close" data-dismiss aria-label="Tanca"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
<?php endforeach; ?>
