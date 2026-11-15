<?php
/**
 * Navegació inferior per al mòbil: els apartats més usats segons el rol.
 * @var string $activeNav
 */
use App\Core\Gate;

$items = Gate::allows('attendance.view')
    ? [['dashboard', '/', 'dashboard', 'Inici'], ['calendar', '/calendari', 'calendar', 'Calendari'], ['attendance', '/assistencia', 'checklist', 'Llista'], ['matches', '/partits', 'trophy', 'Partits']]
    : [['dashboard', '/', 'dashboard', 'Inici'], ['calendar', '/calendari', 'calendar', 'Calendari'], ['callups', '/convocatories', 'clipboard', 'Convocat.'], ['stats', '/estadistiques', 'chart', 'Estadíst.']];
$inMenu = !in_array($activeNav, array_column($items, 0), true);
?>
<nav class="bottomnav" aria-label="Navegació ràpida">
  <?php foreach ($items as [$key, $path, $ic, $label]): $on = $activeNav === $key; ?>
    <a href="<?= e(url($path)) ?>" class="<?= $on ? 'is-active' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
  <button type="button" class="<?= $inMenu ? 'is-active' : '' ?>" data-sidebar-open aria-controls="sidebar"><?= icon('menu') ?><span>Menú</span></button>
</nav>
