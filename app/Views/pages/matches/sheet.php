<?php
/** @var array $m @var array $rows */
use App\Repositories\CallupRepository as C;
$any = (bool)array_filter($rows, function ($r) { return (int)$r['in_sheet'] === 1; });
$numIn = function (string $name, $value, string $label, string $ph = '0') {
    return '<label class="num-field"><span>' . e($label) . '</span><input type="number" min="0" max="999" inputmode="numeric" name="' . e($name) . '" value="' . e($value === null ? '' : (string)$value) . '" placeholder="' . e($ph) . '"></label>';
};
?>
<div class="take-page sheet-page">
  <a class="back-link" href="<?= e(url('/partits/' . (int)$m['id'])) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Partit</a>
  <section class="panel take-head">
    <div>
      <h2 class="panel-title"><?= e($m['team_name']) ?> <?= $m['is_home'] ? 'vs' : '@' ?> <?= e($m['opponent']) ?></h2>
      <p class="muted small"><?= e(ucfirst(fdate($m['starts_at'], 'full'))) ?><?= $m['status'] === 'jugat' ? ' · Resultat ' . (int)$m['goals_for'] . '–' . (int)$m['goals_against'] : '' ?></p>
    </div>
  </section>
  <p class="hint small">Marca qui ha jugat i anota només les dades que tinguis. Les caselles buides de porteria es deixen sense dades (no compten com a zero).</p>

  <?php if (!$rows): ?>
    <section class="panel"><?= component('empty_state', ['icon' => 'users', 'text' => 'Aquest equip no té jugadors/es.']) ?></section>
  <?php else: ?>
  <form method="post" action="<?= e(url('/partits/' . (int)$m['id'] . '/acta')) ?>">
    <?= csrf_field() ?>
    <ul class="roll">
      <?php foreach ($rows as $r):
          $pid = (int)$r['id'];
          $on = $any ? (int)$r['in_sheet'] === 1 : $r['callup_response'] === 'confirmat';
          $gk = $r['position'] === 'porter';
          $n = 'p[' . $pid . ']'; ?>
        <li class="roll-item sheet-item<?= $on ? ' st-present' : '' ?>">
          <label class="roll-top sheet-toggle">
            <input type="checkbox" name="<?= $n ?>[played]" value="1"<?= $on ? ' checked' : '' ?> data-played>
            <span class="dorsal"><?= $r['dorsal'] !== null ? (int)$r['dorsal'] : '–' ?></span>
            <span class="roll-name"><strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong>
              <?php if ($gk): ?><span class="pill pill-orange">Porter/a</span><?php endif; ?>
              <?php if ((int)$r['guest'] === 1): ?><span class="pill pill-muted">Puja de categoria</span><?php endif; ?>
              <?php if ($r['callup_response']): [$rl, $rt] = C::RESPONSES[$r['callup_response']]; ?><span class="pill pill-<?= $rt ?>">Conv.: <?= $rl ?></span><?php endif; ?>
            </span>
          </label>
          <div class="sheet-stats"<?= $on ? '' : ' hidden' ?>>
            <?= $numIn($n . '[goals]', (int)$r['in_sheet'] ? $r['goals'] : null, 'Gols') ?>
            <?= $numIn($n . '[assists]', (int)$r['in_sheet'] ? $r['assists'] : null, 'Assist.') ?>
            <?= $numIn($n . '[blue_cards]', (int)$r['in_sheet'] ? $r['blue_cards'] : null, 'T. blava') ?>
            <?= $numIn($n . '[red_cards]', (int)$r['in_sheet'] ? $r['red_cards'] : null, 'T. vermella') ?>
            <?php if ($gk): ?>
              <div class="gk-grid">
                <?= $numIn($n . '[gk_shots]', $r['gk_shots'], 'Llançaments rebuts', '–') ?>
                <?= $numIn($n . '[gk_saves]', $r['gk_saves'], 'Aturades', '–') ?>
                <?= $numIn($n . '[gk_goals_against]', $r['gk_goals_against'], 'Gols encaixats', '–') ?>
                <?= $numIn($n . '[gk_penalties_faced]', $r['gk_penalties_faced'], 'Penals afrontats', '–') ?>
                <?= $numIn($n . '[gk_penalties_saved]', $r['gk_penalties_saved'], 'Penals aturats', '–') ?>
                <?= $numIn($n . '[gk_free_hits_faced]', $r['gk_free_hits_faced'], 'Faltes directes afrontades', '–') ?>
                <?= $numIn($n . '[gk_free_hits_saved]', $r['gk_free_hits_saved'], 'Faltes directes aturades', '–') ?>
              </div>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="take-footer">
      <span class="take-count" data-sheet-count aria-live="polite"></span>
      <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Desa l'acta</button>
    </div>
  </form>
  <?php endif; ?>
</div>
