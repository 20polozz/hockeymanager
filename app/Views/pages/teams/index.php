<?php
/** @var array $teams */
use App\Core\Gate;

$canManage = Gate::allows('teams.manage');
?>
<?php if ($canManage): ?>
  <div class="page-head page-head-end">
    <a class="btn" href="<?= e(url('/equips/categories')) ?>"><?= icon('settings', 'icon icon-sm') ?> Categories</a>
    <a class="btn btn-primary" href="<?= e(url('/equips/nou')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nou equip</a>
  </div>
<?php endif; ?>
<?php if (!$teams): ?>
  <section class="panel">
    <?= component('empty_state', ['icon' => 'shield', 'text' => 'Encara no hi ha equips a la temporada actual.']) ?>
  </section>
<?php else: ?>
  <div class="team-grid">
    <?php foreach ($teams as $t): ?>
      <article class="team-card" style="--team: <?= e($t['color']) ?>">
        <div class="team-card-head">
          <span class="team-badge"><?= icon('shield') ?></span>
          <div>
            <h2 class="team-name"><?= e($t['name']) ?></h2>
            <span class="muted small"><?= e((string)$t['category']) ?> · <?= e($t['season']) ?></span>
          </div>
        </div>
        <div class="team-figures">
          <div><strong><?= (int)$t['players'] ?></strong><span>jugadors/es</span></div>
          <div><strong><?= (int)$t['goalkeepers'] ?></strong><span>porters/es</span></div>
          <div><strong><?= count($t['staff']) ?></strong><span>staff</span></div>
        </div>
        <div class="team-staff">
          <?php if ($t['staff']): foreach ($t['staff'] as $s): ?>
            <span class="person"><span class="avatar avatar-xs"><?= e(initials($s['name'])) ?></span><?= e($s['name']) ?> <span class="muted small">(<?= $s['staff_role'] === 'principal' ? 'principal' : 'ajudant' ?>)</span></span>
          <?php endforeach; else: ?>
            <span class="muted small">Sense entrenador assignat</span>
          <?php endif; ?>
        </div>
        <div class="card-actions">
          <a class="btn" href="<?= e(url('/jugadors', ['equip' => (int)$t['id']])) ?>">Veure jugadors/es <?= icon('arrow-right', 'icon icon-sm') ?></a>
          <?php if ($canManage): ?>
            <a class="btn" href="<?= e(url('/equips/' . (int)$t['id'] . '/editar')) ?>">Edita</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
