<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Gate;
use App\Core\TeamScope;
use App\Repositories\PlayerRepository;

/**
 * Cercador global de la barra superior.
 */
final class SearchController extends Controller
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $q = mb_substr($q, 0, 80);

        $players = [];
        if (mb_strlen($q) >= 2 && Gate::allows('players.view')) {
            $players = PlayerRepository::listForTeams(TeamScope::ids(), $q);
        }
        $teams = [];
        if (mb_strlen($q) >= 2) {
            foreach (TeamScope::teams() as $t) {
                if (mb_stripos($t['name'] . ' ' . ($t['category'] ?? ''), $q) !== false) {
                    $teams[] = $t;
                }
            }
        }

        $this->view('pages/search', [
            'pageTitle' => 'Cerca',
            'q'         => $q,
            'players'   => array_slice($players, 0, 30),
            'teams'     => $teams,
        ]);
    }
}
