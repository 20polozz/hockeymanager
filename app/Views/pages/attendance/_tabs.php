<?php /** @var string $tab @var int $team */ $q = $team > 0 ? ['equip' => $team] : []; ?>
<nav class="tabs" aria-label="Seccions d'assistència">
  <a href="<?= e(url('/assistencia', $q)) ?>" class="<?= $tab === 'list' ? 'is-active' : '' ?>"<?= $tab === 'list' ? ' aria-current="page"' : '' ?>><?= icon('checklist', 'icon icon-sm') ?> Passar llista</a>
  <a href="<?= e(url('/assistencia/estadistiques', $q)) ?>" class="<?= $tab === 'stats' ? 'is-active' : '' ?>"<?= $tab === 'stats' ? ' aria-current="page"' : '' ?>><?= icon('chart', 'icon icon-sm') ?> Estadístiques</a>
</nav>
