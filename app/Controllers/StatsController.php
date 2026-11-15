<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\TeamScope;
use App\Repositories\MatchRepository;
use App\Repositories\StatsRepository;

final class StatsController extends Controller
{
    public function index(): void
    {
        $seasons = MatchRepository::seasons();
        $current = 0;
        foreach ($seasons as $s) {
            if ((int)$s['is_current'] === 1) {
                $current = (int)$s['id'];
            }
        }
        $season = isset($_GET['temporada']) ? (int)$_GET['temporada'] : $current;
        $ids = competition_filter_ids();
        $user = Auth::user();
        // Un jugador/a només veu les seves estadístiques individuals
        $only = Auth::hasRole(Auth::ROLE_PLAYER) ? (int)($user['player_id'] ?? 0) : null;

        $this->view('pages/stats/index', [
            'pageTitle'   => 'Estadístiques',
            'activeNav'   => 'stats',
            'seasons'     => $seasons,
            'season'      => $season,
            'teams'       => TeamScope::competitionTeams(),
            'team'        => team_filter(),
            'isPlayer'    => $only !== null,
            'summary'     => StatsRepository::teamSummary($ids, $season),
            'results'     => StatsRepository::results($ids, $season, 12),
            'players'     => StatsRepository::players($ids, $season, $only),
            'goalkeepers' => StatsRepository::goalkeepers($ids, $season, $only),
            'attendance'  => StatsRepository::trainingAttendance($ids, $season),
        ]);
    }
}
