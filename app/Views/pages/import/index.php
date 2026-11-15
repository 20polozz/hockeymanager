<?php
/** @var array $connectors */
$tone = ['disponible' => 'green', 'pendent' => 'orange', 'no_configurat' => 'muted'];
$label = ['disponible' => 'Disponible', 'pendent' => 'Pendent', 'no_configurat' => 'No configurat'];
?>
<div class="form-page form-page-wide">
  <a class="back-link" href="<?= e(url('/configuracio')) ?>"><?= icon('chevron-right', 'icon icon-sm flip') ?> Configuració</a>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Importar jugadors/es des d\'un CSV']) ?>
    <p class="muted">Exporta el full de càlcul com a CSV amb aquestes columnes (la primera fila pot ser la capçalera):
      <code>nom; cognoms; data_naixement; posicio; equip; dorsal</code>. La data pot ser <code>dd/mm/aaaa</code>, la posició <code>jugador</code> o <code>porter</code>, i l'equip ha de tenir el mateix nom que a l'app.</p>
    <form method="post" action="<?= e(url('/configuracio/importacio/csv')) ?>" enctype="multipart/form-data" class="add-ex">
      <?= csrf_field() ?>
      <label class="field grow"><span>Fitxer CSV (màx. 1 MB)</span><input type="file" name="file" accept=".csv,text/csv" required></label>
      <button class="btn btn-primary" type="submit"><?= icon('arrow-right', 'icon icon-sm') ?> Previsualitza</button>
    </form>
    <p class="small"><a href="<?= e(url('/configuracio/importacio/plantilla')) ?>">Descarrega una plantilla d'exemple</a> · Res es desa fins que confirmis la previsualització.</p>
  </section>

  <section class="panel">
    <?= component('panel_head', ['title' => 'Connectors de dades']) ?>
    <p class="muted small">Cada font de dades és un connector independent. Les fonts externes s'activaran només quan hi hagi una API oficial o l'autorització del proveïdor (no es fa scraping).</p>
    <?php foreach ($connectors as $c): $st = $c->status(); ?>
      <div class="connector">
        <span class="tile tile-sm tone-<?= $tone[$st['state']] ?? 'muted' ?>"><?= icon($st['state'] === 'disponible' ? 'check' : 'database', 'icon icon-sm') ?></span>
        <div class="connector-body">
          <strong><?= e($c->name()) ?></strong> <span class="pill pill-<?= $tone[$st['state']] ?? 'muted' ?>"><?= e($label[$st['state']] ?? $st['state']) ?></span>
          <p class="small"><?= e($c->description()) ?></p>
          <p class="small muted"><?= e($st['note']) ?> · Dades: <?= e(implode(', ', $c->capabilities())) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
</div>
