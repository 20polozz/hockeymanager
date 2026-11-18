<?php
/** @var array $categories @var array|null $editing */
$v = function (string $k) use ($editing): string { return form_val($editing, $k); };
$n = count($categories);
?>
<div class="form-page form-page-wide">
  <a class="back-link" href="<?= e(url('/equips')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Equips</a>
  <p class="hint small">Ordena-les de la més jove a la més gran: a les convocatòries es poden convocar jugadors/es de la mateixa categoria o de les inferiors. Una categoria amb equips no es pot eliminar.</p>

  <section class="panel panel-flush">
    <?php if (!$categories): ?>
      <?= component('empty_state', ['icon' => 'shield', 'text' => 'Encara no hi ha cap categoria.']) ?>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Ordre</th><th>Categoria</th><th>Edats</th><th>Equips</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($categories as $i => $c): ?>
              <tr>
                <td class="row-actions row-actions-start">
                  <?php foreach ([['up', 'Puja', 'rot-up', $i === 0], ['down', 'Baixa', 'rot-down', $i === $n - 1]] as [$dir, $lbl, $rot, $dis]): ?>
                    <form method="post" action="<?= e(url('/equips/categories/' . (int)$c['id'] . '/moure')) ?>">
                      <?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>">
                      <button class="icon-btn bordered" type="submit" aria-label="<?= e($lbl . ' ' . $c['name']) ?>"<?= $dis ? ' disabled' : '' ?>><?= icon('chevron-right', 'icon icon-sm ' . $rot) ?></button>
                    </form>
                  <?php endforeach; ?>
                </td>
                <td><strong><?= e($c['name']) ?></strong></td>
                <td class="muted"><?= $c['min_age'] !== null || $c['max_age'] !== null ? e(($c['min_age'] ?? '…') . '–' . ($c['max_age'] ?? '…') . ' anys') : '—' ?></td>
                <td class="wrap"><?= (int)$c['teams'] ? e($c['team_names']) : '<span class="muted small">Cap equip</span>' ?></td>
                <td class="row-actions">
                  <a class="btn btn-sm" href="<?= e(url('/equips/categories', ['editar' => (int)$c['id']])) ?>#form">Edita</a>
                  <form method="post" action="<?= e(url('/equips/categories/' . (int)$c['id'] . '/eliminar')) ?>" data-confirm="Segur que vols eliminar la categoria «<?= e($c['name']) ?>»?">
                    <?= csrf_field() ?><button class="btn-trash" type="submit" aria-label="Elimina la categoria <?= e($c['name']) ?>" title="<?= (int)$c['teams'] ? 'Té equips assignats' : 'Elimina' ?>"<?= (int)$c['teams'] ? ' disabled' : '' ?>><?= icon('trash', 'icon icon-sm') ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel" id="form">
    <?= component('panel_head', ['title' => $editing ? 'Edita «' . $editing['name'] . '»' : 'Nova categoria']) ?>
    <form class="form" method="post" action="<?= e(url($editing ? '/equips/categories/' . (int)$editing['id'] : '/equips/categories')) ?>">
      <?= csrf_field() ?>
      <div class="field-row field-row-3">
        <label class="field"><span>Nom</span><input type="text" name="name" value="<?= e($v('name')) ?>" required maxlength="40" placeholder="Ex.: Fem 19"<?= $editing ? ' autofocus' : '' ?>></label>
        <label class="field"><span>Edat mínima <small class="muted">(opcional)</small></span><input type="number" name="min_age" value="<?= e($v('min_age')) ?>" min="0" max="99"></label>
        <label class="field"><span>Edat màxima <small class="muted">(opcional)</small></span><input type="number" name="max_age" value="<?= e($v('max_age')) ?>" min="0" max="99"></label>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $editing ? 'Desa els canvis' : 'Afegeix la categoria' ?></button>
        <?php if ($editing): ?><a class="btn" href="<?= e(url('/equips/categories')) ?>">Cancel·la</a><?php endif; ?>
      </div>
    </form>
  </section>
</div>
