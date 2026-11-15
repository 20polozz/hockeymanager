<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\CallupRepository;
use App\Repositories\MatchRepository;

final class CallupController extends Controller
{
    public function index(): void
    {
        if (Gate::allows('callups.manage')) {
            $this->view('pages/callups/staff', [
                'pageTitle' => 'Convocatòries',
                'activeNav' => 'callups',
                'teams'     => TeamScope::teams(),
                'team'      => team_filter(),
                'rows'      => CallupRepository::upcomingForStaff(team_filter_ids()),
            ]);
            return;
        }
        $user = Auth::user();
        $this->view('pages/callups/player', [
            'pageTitle' => 'Convocatòries',
            'activeNav' => 'callups',
            'rows'      => !empty($user['player_id']) ? CallupRepository::forPlayer((int)$user['player_id']) : [],
            'linked'    => !empty($user['player_id']),
        ]);
    }

    public function edit(int $matchId): void
    {
        $m = $this->match($matchId);
        $callup = CallupRepository::forMatch($matchId);
        $selected = $callup ? array_map('intval', array_column($callup['players'], 'player_id')) : [];
        $this->view('pages/callups/form', [
            'pageTitle' => 'Convocatòria',
            'activeNav' => 'callups',
            'm'         => $m,
            'callup'    => $callup,
            'selected'  => $selected,
            'eligible'  => $eligible = CallupRepository::eligible($m),
            'away'      => \App\Repositories\AbsenceRepository::onDate(substr((string)$m['starts_at'], 0, 10), array_column($eligible, 'id')),
        ]);
    }

    public function save(int $matchId): void
    {
        $m = $this->match($matchId);
        $allowed = array_map('intval', array_column(CallupRepository::eligible($m), 'id'));
        $ids = array_values(array_intersect(array_map('intval', (array)($_POST['players'] ?? [])), $allowed));
        $send = ($_POST['send'] ?? '') === '1';
        if ($send && !$ids) {
            flash('error', 'Tria com a mínim un jugador/a per enviar la convocatòria.');
            redirect('/partits/' . $matchId . '/convocatoria');
        }
        $meeting = null;
        $mt = trim((string)($_POST['meeting_time'] ?? ''));
        if ($mt !== '') {
            $meeting = parse_datetime(substr((string)$m['starts_at'], 0, 10), $mt);
            if ($meeting === null) {
                flash('error', 'L\'hora de citació no és vàlida.');
                redirect('/partits/' . $matchId . '/convocatoria');
            }
        }
        $wasSent = ($c = CallupRepository::forMatch($matchId)) && $c['sent_at'] !== null;
        CallupRepository::save($matchId, $ids, mb_substr(trim((string)($_POST['message'] ?? '')), 0, 2000), $meeting, $send);
        if ($send && !$wasSent) {
            ActivityLog::add('callup', 'Ha enviat la convocatòria contra ' . $m['opponent'] . ' (' . count($ids) . ' jugadors/es)', (int)$m['team_id']);
            flash('success', 'Convocatòria enviada a ' . count($ids) . ' jugadors/es. La veuran a l\'app i podran confirmar-la.');
        } else {
            flash('success', $wasSent ? 'Convocatòria actualitzada.' : 'Esborrany desat (encara no s\'ha enviat).');
        }
        redirect('/partits/' . $matchId);
    }

    public function destroy(int $matchId): void
    {
        $this->match($matchId);
        CallupRepository::delete($matchId);
        flash('success', 'Convocatòria eliminada.');
        redirect('/partits/' . $matchId);
    }

    /** Resposta del jugador/a: confirmar o rebutjar. */
    public function respond(int $callupId): void
    {
        $user = Auth::user();
        $response = (string)($_POST['response'] ?? '');
        if (empty($user['player_id']) || !in_array($response, ['confirmat', 'rebutjat'], true)) {
            throw new HttpException(400);
        }
        $ok = CallupRepository::respond($callupId, (int)$user['player_id'], $response, mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255));
        if (!$ok) {
            flash('error', 'Ja no es pot respondre aquesta convocatòria.');
        } else {
            flash('success', $response === 'confirmat' ? 'Has confirmat la convocatòria. Fins aviat!' : 'Has indicat que no hi podràs anar.');
        }
        redirect('/convocatories');
    }

    /**
     * @return array<string,mixed>
     */
    private function match(int $id): array
    {
        $m = MatchRepository::find($id);
        if (!$m || !TeamScope::canAccess((int)$m['team_id'])) {
            throw new HttpException(404);
        }
        return $m;
    }
}
