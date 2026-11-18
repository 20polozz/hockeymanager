<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Gate;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\DashboardRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $isPlayer = Auth::hasRole(Auth::ROLE_PLAYER);
        $repo = new DashboardRepository(TeamScope::ids());

        $attendance = $repo->attendanceRate(30);
        if ($isPlayer && !empty($user['player_id'])) {
            $attendance = [
                'percent'  => DashboardRepository::playerAttendanceRate((int)$user['player_id'], 30),
                'sessions' => null,
            ];
        }


        $this->view('pages/dashboard', [
            'pageTitle'       => 'Dashboard',
            'activeNav'       => 'dashboard',
            'isPlayer'        => $isPlayer,
            'hasTeams'        => !$repo->isEmpty(),
            'totalPlayers'    => $repo->totalPlayers(),
            'attendance'      => $attendance,
            'nextTraining'    => $repo->nextTraining(),
            'quickCards'      => $this->quickCards($repo),
            'upcoming'        => $repo->upcomingEvents(6),
            'activity'        => $isPlayer ? [] : ActivityLog::recent(TeamScope::ids(), Auth::isManager(), 6),
            'attendanceTeams' => $isPlayer ? [] : $repo->attendanceByTeam(30),
            'unavailable'     => $isPlayer ? [] : \App\Repositories\AbsenceRepository::upcoming(TeamScope::ids(), 14),
        ]);
    }

    /**
     * Targetes d'accés ràpid, només les que el rol pot obrir.
     * @return array<int,array<string,mixed>>
     */
    private function quickCards(DashboardRepository $repo): array
    {
        $last = $repo->lastSession();
        $lastText = $last
            ? sprintf('Última: %s · %d presents, %d absents', $last['team_name'], $last['counts']['present'] + $last['counts']['retard'], $last['counts']['absent'] + $last['counts']['justificat'])
            : 'Encara no s\'ha passat cap llista';

        $cards = [
            ['can' => 'attendance.view', 'path' => '/assistencia', 'icon' => 'checklist', 'tone' => 'green',  'title' => 'Assistència',   'text' => 'Passa llista i consulta l\'historial', 'meta' => $lastText],
            ['can' => 'calendar.view',   'path' => '/calendari',   'icon' => 'calendar',  'tone' => 'blue',   'title' => 'Calendari',     'text' => 'Entrenaments, gimnàs i esdeveniments', 'meta' => null],
            ['can' => 'exercises.view',  'path' => '/exercicis',   'icon' => 'cone',      'tone' => 'orange', 'title' => 'Exercicis',     'text' => 'Biblioteca d\'exercicis i sessions', 'meta' => null],
            ['can' => 'players.view',    'path' => '/jugadors',    'icon' => 'users',     'tone' => 'navy',   'title' => 'Jugadors/es',      'text' => 'Fitxes i informació individual', 'meta' => null],
            ['can' => 'players.own',     'path' => '/perfil',      'icon' => 'user',      'tone' => 'navy',   'title' => 'El meu perfil', 'text' => 'Fitxa, equip i historial', 'meta' => null],
            ['can' => 'teams.view',      'path' => '/equips',      'icon' => 'shield',    'tone' => 'red',    'title' => 'Equips',        'text' => 'Equips, categories i staff', 'meta' => null],
        ];
        return array_values(array_filter($cards, function (array $c): bool {
            return Gate::allows($c['can']);
        }));
    }
}
