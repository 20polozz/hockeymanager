<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\TeamScope;
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
            'pageTitle'  => 'Jugadors',
            'activeNav'  => 'players',
            'search'     => $search,
            'teams'      => TeamScope::teams(),
            'activeTeam' => $teamId,
            'players'    => PlayerRepository::listForTeams($teamId > 0 ? [$teamId] : TeamScope::ids(), $search),
        ]);
    }

    public function show(int $id): void
    {
        $player = PlayerRepository::find($id);
        // Només jugadors d'equips que l'usuari pot veure
        if (!$player || !array_intersect(PlayerRepository::currentTeamIds($id), TeamScope::ids())) {
            throw new HttpException(404);
        }
        $this->renderProfile($player, 'players');
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
        ]);
    }
}
