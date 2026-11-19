<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\PlayerRepository;

final class PlayerController extends Controller
{
    public function index(): void
    {
        $search = trim((string)($_GET['q'] ?? ''));
        // Filtre propi de la pàgina (?equip=ID), independent del selector global
        $teamId = (int)($_GET['equip'] ?? 0);
        if ($teamId > 0 && !TeamScope::canAccess($teamId)) {
            $teamId = 0;
        }
        $this->view('pages/players/index', [
            'pageTitle'  => 'Jugadors/es',
            'activeNav'  => 'players',
            'search'     => $search,
            'teams'      => TeamScope::teams(),
            'activeTeam' => $teamId,
            'players'    => $players = PlayerRepository::listForTeams($teamId > 0 ? [$teamId] : TeamScope::ids(), $search),
            'away'       => \App\Repositories\AbsenceRepository::onDate(date('Y-m-d'), array_column($players, 'id')),
        ]);
    }

    public function show(int $id): void
    {
        $player = PlayerRepository::find($id);
        // Només jugadors d'equips que l'usuari pot veure
        if (!$player || (!Auth::isManager() && !array_intersect(PlayerRepository::currentTeamIds($id), TeamScope::ids()))) {
            throw new HttpException(404);
        }
        $this->renderProfile($player, 'players');
    }

    public function create(): void
    {
        $preset = (int)($_GET['equip'] ?? 0);
        $this->form(null, TeamScope::canAccess($preset) ? [$preset => null] : []);
    }

    public function store(): void
    {
        [$data, $teams, $errors] = PlayerRepository::validate($_POST, TeamScope::ids());
        if ($errors) {
            $this->fail($errors, '/jugadors/nou');
        }
        $id = PlayerRepository::create($data);
        PlayerRepository::syncTeams($id, $teams);
        $name = $data['first_name'] . ' ' . $data['last_name'];
        ActivityLog::add('player', 'Ha donat d\'alta ' . $name, (int)array_key_first($teams));
        forget_input();
        flash('success', $name . ' s\'ha afegit a la plantilla.');
        redirect('/jugadors/' . $id);
    }

    public function edit(int $id): void
    {
        $player = $this->findManageable($id);
        $this->form($player, PlayerRepository::currentMemberships($id));
    }

    public function update(int $id): void
    {
        $this->findManageable($id);
        [$data, $teams, $errors] = PlayerRepository::validate($_POST, TeamScope::ids());
        if ($errors) {
            $this->fail($errors, '/jugadors/' . $id . '#fitxa');
        }
        PlayerRepository::update($id, $data);
        PlayerRepository::syncTeams($id, $teams, Auth::isManager() ? null : TeamScope::ids());
        ActivityLog::add('player', 'Ha editat la fitxa de ' . $data['first_name'] . ' ' . $data['last_name'], (int)array_key_first($teams));
        forget_input();
        flash('success', 'Fitxa desada.');
        redirect('/jugadors/' . $id . '#fitxa');
    }

    public function deactivate(int $id): void
    {
        $player = $this->findManageable($id);
        PlayerRepository::deactivate($id);
        ActivityLog::add('player', 'Ha donat de baixa ' . $player['first_name'] . ' ' . $player['last_name']);
        flash('success', $player['first_name'] . ' ' . $player['last_name'] . ' ja no apareix a la plantilla. L\'historial es conserva.');
        redirect('/jugadors');
    }

    /**
     * @param array<string,mixed>|null $player
     * @param array<int,int|null> $memberships
     */
    private function form(?array $player, array $memberships): void
    {
        $this->view('pages/players/form', [
            'pageTitle'   => $player ? 'Edita la fitxa' : 'Nou jugador/a',
            'activeNav'   => 'players',
            'player'      => $player,
            'memberships' => $memberships,
            'teams'       => TeamScope::teams(),
        ]);
        forget_input();
    }

    /**
     * @return array<string,mixed>
     */
    private function findManageable(int $id): array
    {
        $player = PlayerRepository::find($id);
        if (!$player || (int)$player['active'] !== 1) {
            throw new HttpException(404);
        }
        // Un entrenador només gestiona les jugadores dels seus equips
        if (!Auth::isManager() && !array_intersect(PlayerRepository::currentTeamIds($id), TeamScope::ids())) {
            throw new HttpException(403, 'Només pots editar les jugadores dels teus equips.');
        }
        return $player;
    }

    /**
     * @param array<int,string> $errors
     */
    private function fail(array $errors, string $back): void
    {
        remember_input($_POST);
        foreach ($errors as $err) {
            flash('error', $err);
        }
        redirect($back);
    }

    public function own(): void
    {
        $user = Auth::user();
        $player = !empty($user['player_id']) ? PlayerRepository::find((int)$user['player_id']) : null;
        if (!$player) {
            $this->view('pages/players/no_profile', ['pageTitle' => 'El meu perfil', 'activeNav' => 'profile']);
            return;
        }
        $this->renderProfile($player, 'profile');
    }

    /**
     * @param array<string,mixed> $player
     */
    private function renderProfile(array $player, string $nav): void
    {
        $id = (int)$player['id'];
        $this->view('pages/players/show', [
            'pageTitle'  => $player['first_name'] . ' ' . $player['last_name'],
            'activeNav'  => $nav,
            'player'     => $player,
            'history'    => PlayerRepository::teamHistory($id),
            'attendance' => PlayerRepository::attendanceSummary($id),
            'attHistory' => \App\Repositories\AttendanceRepository::playerHistory($id, 10),
            'canManage'  => Gate::allows('players.manage') && (int)$player['active'] === 1
                && (Auth::isManager() || (bool)array_intersect(PlayerRepository::currentTeamIds($id), TeamScope::ids())),
            'absences'   => \App\Repositories\AbsenceRepository::forPlayer($id),
            'canAbsence' => (int)$player['active'] === 1 && AbsenceController::canManage($id),
            'memberships' => PlayerRepository::currentMemberships($id),
            'teams'      => TeamScope::teams(),
        ]);
        forget_input();
    }
}
