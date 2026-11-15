<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\TeamScope;

/**
 * Selector d'equip de la barra superior (filtra el dashboard i els mòduls).
 */
final class TeamSelectorController extends Controller
{
    public function update(): void
    {
        $teamId = (int)($_POST['team_id'] ?? 0);
        if ($teamId > 0 && !TeamScope::canAccess($teamId)) {
            flash('error', 'No tens accés a aquest equip.');
        } else {
            TeamScope::setActive($teamId);
        }
        $back = (string)($_POST['back'] ?? '/');
        redirect(strpos($back, '/') === 0 && strpos($back, '//') !== 0 ? $back : '/');
    }
}
