<?php
/**
 * Filtre per equip en forma de botons (?equip=ID).
 * @var array $teams  @var int $active  @var string $path  @var array $query (altres paràmetres a conservar)
 */
$query = $query ?? [];
if (count($teams) < 2) {
    return;
}
?>
<nav class="filter-chips" aria-label="Filtra per equip">
  <a class="chip<?= $active === 0 ? ' is-active' : '' ?>" href="<?= e(url($path, $query)) ?>"<?= $active === 0 ? ' aria-current="true"' : '' ?>>Tots els equips</a>
  <?php foreach ($teams as $t): $on = $active === (int)$t['id']; ?>
    <a class="chip<?= $on ? ' is-active' : '' ?>" href="<?= e(url($path, $query + ['equip' => (int)$t['id']])) ?>"<?= $on ? ' aria-current="true"' : '' ?>><span class="team-dot" style="--team: <?= e($t['color']) ?>"></span><?= e($t['name']) ?></a>
  <?php endforeach; ?>
</nav>
