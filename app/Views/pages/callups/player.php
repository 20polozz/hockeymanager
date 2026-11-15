<?php
/** @var array $rows @var bool $linked */
use App\Repositories\CallupRepository as C;
?>
<?php if (!$linked): ?>
  <section class="panel"><?= component('empty_state', ['icon' => 'user', 'text' => 'El teu compte encara no està vinculat a cap fitxa de jugador/a. Demana-ho al coordinador.']) ?></section>
<?php elseif (!$rows): ?>
  <section class="panel"><?= component('empty_state', ['icon' => 'clipboard', 'text' => 'No tens cap convocatòria pendent.']) ?></section>
<?php else: ?>
  <div class="callup-cards">
    <?php foreach ($rows as $r): [$rl, $rt] = C::RESPONSES[$r['response']]; $open = strtotime((string)$r['starts_at']) > time(); ?>
      <article class="panel callup-card resp-<?= e($r['response']) ?>">
        <div class="callup-card-head">
          <span class="date-chip"><span class="date-chip-month"><?= e(fdate($r['starts_at'], 'monthshort')) ?></span><span class="date-chip-day"><?= e(fdate($r['starts_at'], 'daynum')) ?></span></span>
          <div>
            <h2 class="panel-title"><?= e($r['team_name']) ?> <?= $r['is_home'] ? 'vs' : '@' ?> <?= e($r['opponent']) ?></h2>
            <ul class="meta-list">
              <li><?= icon('clock', 'icon icon-sm') ?> <?= e(ucfirst(fdate($r['starts_at'], 'full'))) ?></li>
              <?php if ($r['meeting_at']): ?><li><?= icon('flag', 'icon icon-sm') ?> Citació <?= e(fdate($r['meeting_at'], 'time')) ?></li><?php endif; ?>
              <?php if ($r['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <?= e($r['location']) ?></li><?php endif; ?>
            </ul>
          </div>
          <span class="pill pill-<?= $rt ?>"><?= $rl ?></span>
        </div>
        <?php if ($r['message']): ?><p class="pre-line callup-msg"><?= e($r['message']) ?></p><?php endif; ?>
        <?php if ($open): ?>
          <form method="post" action="<?= e(url('/convocatories/' . (int)$r['callup_id'] . '/resposta')) ?>" class="respond-form">
            <?= csrf_field() ?>
            <input type="text" name="note" maxlength="255" value="<?= e((string)$r['response_note']) ?>" placeholder="Comentari (opcional): arribaré tard, lesió…">
            <div class="respond-actions">
              <button class="btn btn-ok" type="submit" name="response" value="confirmat"><?= icon('check', 'icon icon-sm') ?> Hi aniré</button>
              <button class="btn btn-danger" type="submit" name="response" value="rebutjat"><?= icon('x', 'icon icon-sm') ?> No hi puc anar</button>
            </div>
          </form>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
