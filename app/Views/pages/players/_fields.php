<?php
/** Camps de la fitxa (nova i edició). @var array|null $player @var array $memberships @var array $teams @var bool $autofocus */
$val = function (string $key) use ($player): string {
    $old = old($key, "\0");
    if ($old !== "\0") {
        return $old;
    }
    return $player !== null && isset($player[$key]) ? (string)$player[$key] : '';
};
// Equips marcats i dorsals (dades antigues del formulari si hi ha hagut un error)
$oldTeams = old_array('teams');
$oldDorsals = old_array('dorsal');
$checked = $oldTeams !== null ? array_map('intval', $oldTeams) : array_keys($memberships);
$dorsalOf = function (int $tid) use ($oldDorsals, $memberships): string {
    if ($oldDorsals !== null) {
        return (string)($oldDorsals[$tid] ?? '');
    }
    return isset($memberships[$tid]) ? (string)$memberships[$tid] : '';
};
$position = $val('position') ?: 'jugador';
?>
      <div class="field-row">
        <label class="field"><span>Nom</span>
          <input type="text" name="first_name" value="<?= e($val('first_name')) ?>" required maxlength="60" autocomplete="off"<?= !empty($autofocus) ? ' autofocus' : '' ?>></label>
        <label class="field"><span>Cognoms</span>
          <input type="text" name="last_name" value="<?= e($val('last_name')) ?>" required maxlength="100" autocomplete="off"></label>
      </div>
      <div class="field-row">
        <label class="field"><span>Data de naixement <small class="muted">(opcional)</small></span>
          <input type="date" name="birth_date" value="<?= e($val('birth_date')) ?>" max="<?= e(date('Y-m-d')) ?>"></label>
        <fieldset class="field">
          <legend>Posició</legend>
          <div class="segmented">
            <label><input type="radio" name="position" value="jugador"<?= $position !== 'porter' ? ' checked' : '' ?>><span>Jugador/a de camp</span></label>
            <label><input type="radio" name="position" value="porter"<?= $position === 'porter' ? ' checked' : '' ?>><span>Porter/a</span></label>
          </div>
        </fieldset>
      </div>

      <fieldset class="field">
        <legend>Equips <small class="muted">(pot jugar a més d'un)</small></legend>
        <?php if (!$teams): ?>
          <p class="muted small">Primer cal crear algun equip.</p>
        <?php endif; ?>
        <div class="team-checks">
          <?php foreach ($teams as $t): $tid = (int)$t['id']; $on = in_array($tid, $checked, true); ?>
            <div class="team-check<?= $on ? ' is-on' : '' ?>">
              <label class="team-check-main">
                <input type="checkbox" name="teams[]" value="<?= $tid ?>"<?= $on ? ' checked' : '' ?> data-toggle-row>
                <span class="team-dot" style="--team: <?= e($t['color']) ?>"></span>
                <span><?= e($t['name']) ?></span>
              </label>
              <label class="team-check-dorsal">
                <span>Dorsal</span>
                <input type="text" name="dorsal[<?= $tid ?>]" value="<?= e($dorsalOf($tid)) ?>" inputmode="numeric" pattern="[0-9]{1,2}" maxlength="2" placeholder="–">
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </fieldset>

