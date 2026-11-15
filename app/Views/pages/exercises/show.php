<?php
/** @var array $ex @var array|null $media @var int $usage @var bool $canManage */
use App\Repositories\ExerciseRepository as Ex;
[$catLabel, $tone] = Ex::CATEGORIES[$ex['category']];
?>
<div class="form-page form-page-wide">
  <a class="back-link" href="<?= e(url('/exercicis')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Exercicis</a>

  <section class="panel">
    <div class="ex-head">
      <div>
        <span class="ex-cat tone-<?= $tone ?>"><?= e($catLabel) ?></span>
        <h2 class="module-title"><?= e($ex['name']) ?></h2>
      </div>
      <form method="post" action="<?= e(url('/exercicis/' . (int)$ex['id'] . '/preferit')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn<?= (int)$ex['favorite'] ? ' btn-on' : '' ?>" aria-pressed="<?= (int)$ex['favorite'] ? 'true' : 'false' ?>"><?= icon('star', 'icon icon-sm') ?> <?= (int)$ex['favorite'] ? 'Preferit' : 'Afegeix als preferits' ?></button>
      </form>
    </div>
    <div class="ex-facts">
      <div><span>Durada</span><strong><?= $ex['duration_min'] ? (int)$ex['duration_min'] . ' min' : '—' ?></strong></div>
      <div><span>Dificultat</span><strong class="diff diff-<?= (int)$ex['difficulty'] ?>"><i></i><i></i><i></i> <?= e(Ex::DIFFICULTY[(int)$ex['difficulty']] ?? '') ?></strong></div>
      <div><span>Edat recomanada</span><strong><?= $ex['recommended_age'] ? e($ex['recommended_age']) : '—' ?></strong></div>
      <div><span>Material</span><strong><?= $ex['material'] ? e($ex['material']) : '—' ?></strong></div>
    </div>
  </section>

  <?php if ($media): ?>
    <section class="panel panel-flush media-panel">
      <?php if ($media['type'] === 'youtube'): ?>
        <div class="video-wrap"><iframe src="<?= e($media['src']) ?>" title="Vídeo de l'exercici" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
      <?php elseif ($media['type'] === 'image'): ?>
        <img src="<?= e($media['src']) ?>" alt="Esquema de l'exercici <?= e($ex['name']) ?>" loading="lazy" referrerpolicy="no-referrer">
      <?php else: ?>
        <a class="media-link" href="<?= e($media['src']) ?>" target="_blank" rel="noopener noreferrer"><?= icon('arrow-right', 'icon icon-sm') ?> Obre la imatge o el vídeo</a>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <div class="dash-grid">
    <section class="panel">
      <?= component('panel_head', ['title' => 'Objectiu']) ?>
      <p class="pre-line"><?= $ex['objective'] ? e($ex['objective']) : '<span class="muted">Sense objectiu definit.</span>' ?></p>
    </section>
    <section class="panel">
      <?= component('panel_head', ['title' => 'Descripció']) ?>
      <p class="pre-line"><?= $ex['description'] ? e($ex['description']) : '<span class="muted">Sense descripció.</span>' ?></p>
    </section>
  </div>

  <p class="muted small ex-foot">
    <?= $ex['author'] ? 'Creat per ' . e($ex['author']) . ' · ' : '' ?>Fet servir en <?= $usage ?> <?= $usage === 1 ? 'sessió' : 'sessions' ?>
  </p>

  <?php if ($canManage): ?>
    <section class="panel danger-zone">
      <a class="btn" href="<?= e(url('/exercicis/' . (int)$ex['id'] . '/editar')) ?>"><?= icon('wrench', 'icon icon-sm') ?> Edita</a>
      <form method="post" action="<?= e(url('/exercicis/' . (int)$ex['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar «<?= e($ex['name']) ?>»?<?= $usage ? ' Es traurà de ' . $usage . ' sessions.' : '' ?>">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"><?= icon('x', 'icon icon-sm') ?> Elimina</button>
      </form>
    </section>
  <?php endif; ?>
</div>
