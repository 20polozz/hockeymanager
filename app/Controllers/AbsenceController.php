<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\AbsenceRepository;
use App\Repositories\ActivityLog;
use App\Repositories\PlayerRepository;

/**
 * Absències i baixes temporals d'una jugadora.
 * Les registra el cos tècnic (de les seves jugadores) o la mateixa jugadora.
 */
final class AbsenceController extends Controller
{
    public function store(int $playerId): void
    {
        $player = $this->player($playerId);
        [$d, $errors] = AbsenceRepository::validate($_POST);
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect($this->back($playerId) . '#absencies');
        }
        AbsenceRepository::create($playerId, $d);
        ActivityLog::add('attendance', 'Ha registrat una absència de ' . $player['first_name'] . ' ' . $player['last_name'] . ' (' . AbsenceRepository::label($d) . ')');
        flash('success', 'Absència registrada.');
        redirect($this->back($playerId) . '#absencies');
    }

    public function finish(int $id): void
    {
        $a = $this->absence($id);
        AbsenceRepository::finish($id);
        flash('success', 'Marcada com a tornada. Ja torna a estar disponible.');
        redirect($this->back((int)$a['player_id']) . '#absencies');
    }

    public function destroy(int $id): void
    {
        $a = $this->absence($id);
        AbsenceRepository::delete($id);
        flash('success', 'Absència eliminada.');
        redirect($this->back((int)$a['player_id']) . '#absencies');
    }

    /** La jugadora mateixa o algú del cos tècnic que la té en algun equip. */
    private function player(int $playerId): array
    {
        $player = PlayerRepository::find($playerId);
        if (!$player || (int)$player['active'] !== 1 || !self::canManage($playerId)) {
            throw new HttpException(404);
        }
        return $player;
    }

    private function absence(int $id): array
    {
        $a = AbsenceRepository::find($id);
        if (!$a) {
            throw new HttpException(404);
        }
        $this->player((int)$a['player_id']);
        // Una jugadora només pot tocar les que ha creat ella
        if (Auth::hasRole(Auth::ROLE_PLAYER) && (int)$a['created_by'] !== Auth::id()) {
            throw new HttpException(403);
        }
        return $a;
    }

    public static function canManage(int $playerId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        if (Auth::hasRole(Auth::ROLE_PLAYER)) {
            return (int)($user['player_id'] ?? 0) === $playerId;
        }
        return Gate::allows('attendance.manage')
            && (Auth::isManager() || (bool)array_intersect(PlayerRepository::currentTeamIds($playerId), TeamScope::ids()));
    }

    private function back(int $playerId): string
    {
        return Auth::hasRole(Auth::ROLE_PLAYER) ? '/perfil' : '/jugadors/' . $playerId;
    }
}
