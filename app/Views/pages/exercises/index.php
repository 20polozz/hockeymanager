<?php
/** @var string $q @var string $cat @var bool $fav @var array $exercises @var bool $canManage */
use App\Repositories\ExerciseRepository as Ex;
$base = array_filter(['q' => $q, 'preferits' => $fav ? '1' : '']);
$self = url('/exercicis', array_filter(['q' => $q, 'categoria' => $cat, 'preferits' => $fav ? '1' : '']));
?>
<div class="page-head">
  <form class="inline-search" method="get" action="<?= e(url('/exercicis')) ?>">
    <?php if ($cat): ?><input type="hidden" name="categoria" value="<?= e($cat) ?>"><?php endif; ?>
    <?php if ($fav): ?><input type="hidden" name="preferits" value="1"><?php endif; ?>
    <label class="visually-hidden" for="ex-search">Cerca exercicis</label>
    <?= icon('search', 'icon icon-sm inline-search-icon') ?><input id="ex-search" type="search" name="q" data-filter=".ex-card" autocomplete="off" value="<?= e($q) ?>" placeholder="Cerca per nom o objectiu…" maxlength="80">
  </form>
  <div class="head-actions">
    <a class="btn<?= $fav ? ' btn-on' : '' ?>" href="<?= e(url('/exercicis', array_filter(['q' => $q, 'categoria' => $cat, 'preferits' => $fav ? '' : '1']))) ?>" aria-pressed="<?= $fav ? 'true' : 'false' ?>"><?= icon('star', 'icon icon-sm') ?> Preferits</a>
    <?php if ($canManage): ?>
      <a class="btn btn-primary" href="<?= e(url('/exercicis/nou')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nou exercici</a>
    <?php endif; ?>
  </div>
</div>

<nav class="filter-chips" aria-label="Categories">
  <a class="chip<?= $cat === '' ? ' is-active' : '' ?>" href="<?= e(url('/exercicis', $base)) ?>">Totes</a>
  <?php foreach (Ex::CATEGORIES as $key => [$label, $tone]): ?>
    <a class="chip<?= $cat === $key ? ' is-active' : '' ?>" href="<?= e(url('/exercicis', $base + ['categoria' => $key])) ?>"><i class="dot dot-<?= $tone ?>"></i><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$exercises): ?>
  <section class="panel">
    <?= component('empty_state', ['icon' => 'cone', 'text' => ($q !== '' || $cat !== '' || $fav) ? 'Cap exercici coincideix amb els filtres.' : 'La biblioteca encara és buida. Crea el primer exercici del club.']) ?>
  </section>
<?php else: ?>
  <div class="ex-grid">
    <?php foreach ($exercises as $x): [$catLabel, $tone] = Ex::CATEGORIES[$x['category']]; ?>
      <article class="ex-card">
        <a class="ex-card-link" href="<?= e(url('/exercicis/' . (int)$x['id'])) ?>">
          <span class="ex-cat tone-<?= $tone ?>"><?= e($catLabel) ?></span>
          <h2 class="ex-name"><?= e($x['name']) ?></h2>
          <?php if ($x['objective']): ?><p class="ex-obj"><?= e($x['objective']) ?></p><?php endif; ?>
          <span class="ex-meta">
            <?php if ($x['duration_min']): ?><span><?= icon('clock', 'icon icon-xs') ?> <?= (int)$x['duration_min'] ?> min</span><?php endif; ?>
            <span class="diff diff-<?= (int)$x['difficulty'] ?>" title="Dificultat"><i></i><i></i><i></i> <?= e(Ex::DIFFICULTY[(int)$x['difficulty']] ?? '') ?></span>
            <?php if ($x['recommended_age']): ?><span><?= icon('users', 'icon icon-xs') ?> <?= e($x['recommended_age']) ?></span><?php endif; ?>
            <?php if ($x['media_url']): ?><span title="Té imatge o vídeo"><?= icon('flag', 'icon icon-xs') ?> Multimèdia</span><?php endif; ?>
          </span>
        </a>
        <form method="post" action="<?= e(url('/exercicis/' . (int)$x['id'] . '/preferit')) ?>" class="ex-fav">
          <?= csrf_field() ?>
          <input type="hidden" name="back" value="<?= e(substr($self, strlen(url('/')) - 1)) ?>">
          <button type="submit" class="icon-btn<?= (int)$x['favorite'] ? ' is-fav' : '' ?>" aria-label="<?= (int)$x['favorite'] ? 'Treu dels preferits' : 'Afegeix als preferits' ?>" aria-pressed="<?= (int)$x['favorite'] ? 'true' : 'false' ?>"><?= icon('star', 'icon icon-sm') ?></button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
