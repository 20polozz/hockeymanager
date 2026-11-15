<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\CallupRepository;
use App\Repositories\MatchRepository;

final class MatchController extends Controller
{
    public function index(): void
    {
        $tab = ($_GET['vista'] ?? '') === 'disputats' ? 'disputats' : 'propers';
        $seasons = MatchRepository::seasons();
        $current = 0;
        foreach ($seasons as $s) {
            if ((int)$s['is_current'] === 1) {
                $current = (int)$s['id'];
            }
        }
        $season = isset($_GET['temporada']) ? (int)$_GET['temporada'] : $current;
        $matches = MatchRepository::list(team_filter_ids(), $tab, $season);

        $record = ['V' => 0, 'E' => 0, 'D' => 0, 'gf' => 0, 'ga' => 0];
        if ($tab === 'disputats') {
            foreach ($matches as $m) {
                $gf = (int)$m['goals_for'];
                $ga = (int)$m['goals_against'];
                $record[$gf > $ga ? 'V' : ($gf < $ga ? 'D' : 'E')]++;
                $record['gf'] += $gf;
                $record['ga'] += $ga;
            }
        }

        $this->view('pages/matches/index', [
            'pageTitle' => 'Partits',
            'activeNav' => 'matches',
            'tab'       => $tab,
            'seasons'   => $seasons,
            'season'    => $season,
            'matches'   => $matches,
            'record'    => $record,
            'teams'     => TeamScope::teams(),
            'team'      => team_filter(),
            'canManage' => Gate::allows('matches.manage'),
        ]);
    }

    public function show(int $id): void
    {
        $m = $this->findOr404($id);
        $this->view('pages/matches/show', [
            'pageTitle'   => $m['team_name'] . ($m['is_home'] ? ' vs ' : ' @ ') . $m['opponent'],
            'activeNav'   => 'matches',
            'm'           => $m,
            'callup'      => Gate::allows('callups.manage') ? CallupRepository::forMatch($id) : null,
            'sheet'       => MatchRepository::sheetSummary($id),
            'canManage'   => Gate::allows('matches.manage'),
            'canCallup'   => Gate::allows('callups.manage'),
        ]);
    }

    public function create(): void
    {
        $this->form(null, ['team_id' => (string)team_filter(), 'is_home' => '1', 'status' => 'programat']);
    }

    public function store(): void
    {
        [$d, $errors] = MatchRepository::validate($_POST, TeamScope::ids());
        if ($errors) {
            $this->fail($errors, '/partits/nou');
        }
        $id = MatchRepository::create($d);
        ActivityLog::add('match', 'Ha programat el partit contra ' . $d['opponent'], $d['team_id']);
        forget_input();
        flash('success', 'Partit creat.');
        redirect('/partits/' . $id);
    }

    public function edit(int $id): void
    {
        $m = $this->findOr404($id);
        $this->form($m, ['date' => substr((string)$m['starts_at'], 0, 10), 'time' => substr((string)$m['starts_at'], 11, 5)]);
    }

    public function update(int $id): void
    {
        $old = $this->findOr404($id);
        [$d, $errors] = MatchRepository::validate($_POST, TeamScope::ids());
        if ($errors) {
            $this->fail($errors, '/partits/' . $id . '/editar');
        }
        MatchRepository::update($id, $d);
        if ($d['status'] === 'jugat' && $old['status'] !== 'jugat') {
            ActivityLog::add('match', 'Ha registrat el resultat contra ' . $d['opponent'] . ' (' . $d['goals_for'] . '-' . $d['goals_against'] . ')', $d['team_id']);
        }
        forget_input();
        flash('success', 'Partit actualitzat.');
        redirect('/partits/' . $id);
    }

    public function destroy(int $id): void
    {
        $m = $this->findOr404($id);
        MatchRepository::delete($id);
        ActivityLog::add('match', 'Ha eliminat el partit contra ' . $m['opponent'], (int)$m['team_id']);
        flash('success', 'Partit eliminat.');
        redirect('/partits');
    }

    /** Acta: qui ha jugat i estadístiques individuals. */
    public function sheet(int $id): void
    {
        $m = $this->findOr404($id);
        $this->view('pages/matches/sheet', [
            'pageTitle' => 'Acta del partit',
            'activeNav' => 'matches',
            'm'         => $m,
            'rows'      => MatchRepository::sheet($m),
        ]);
    }

    public function saveSheet(int $id): void
    {
        $m = $this->findOr404($id);
        $allowed = array_map('intval', array_column(MatchRepository::sheet($m), 'id'));
        if (!$allowed) {
            flash('error', 'Aquest equip no té jugadors/es.');
            redirect('/partits/' . $id);
        }
        $positions = [];
        foreach (Database::all('SELECT id, position FROM players WHERE id IN (' . Database::in($allowed) . ')', $allowed) as $p) {
            $positions[(int)$p['id']] = $p['position'];
        }
        $in = is_array($_POST['p'] ?? null) ? $_POST['p'] : [];
        $num = function ($v, bool $nullable) {
            $v = trim((string)$v);
            if ($v === '') {
                return $nullable ? null : 0;
            }
            return ctype_digit($v) ? min((int)$v, 999) : ($nullable ? null : 0);
        };
        $rows = [];
        foreach ($allowed as $pid) {
            $r = is_array($in[$pid] ?? null) ? $in[$pid] : [];
            if (empty($r['played'])) {
                continue;
            }
            $gk = ($positions[$pid] ?? '') === 'porter';
            $rows[$pid] = [
                'goals'              => min(99, (int)$num($r['goals'] ?? '', false)),
                'assists'            => min(99, (int)$num($r['assists'] ?? '', false)),
                'blue_cards'         => min(9, (int)$num($r['blue_cards'] ?? '', false)),
                'red_cards'          => min(9, (int)$num($r['red_cards'] ?? '', false)),
                'gk_goals_against'   => $gk ? $num($r['gk_goals_against'] ?? '', true) : null,
                'gk_saves'           => $gk ? $num($r['gk_saves'] ?? '', true) : null,
                'gk_shots'           => $gk ? $num($r['gk_shots'] ?? '', true) : null,
                'gk_penalties_faced' => $gk ? $num($r['gk_penalties_faced'] ?? '', true) : null,
                'gk_penalties_saved' => $gk ? $num($r['gk_penalties_saved'] ?? '', true) : null,
                'gk_free_hits_faced' => $gk ? $num($r['gk_free_hits_faced'] ?? '', true) : null,
                'gk_free_hits_saved' => $gk ? $num($r['gk_free_hits_saved'] ?? '', true) : null,
            ];
        }
        MatchRepository::saveSheet($id, $rows);
        $warn = '';
        $goals = array_sum(array_column($rows, 'goals'));
        if ($m['status'] === 'jugat' && $goals !== (int)$m['goals_for']) {
            $warn = ' Atenció: la suma de gols de l\'acta (' . $goals . ') no coincideix amb el resultat (' . (int)$m['goals_for'] . ').';
        }
        ActivityLog::add('match', 'Ha omplert l\'acta del partit contra ' . $m['opponent'], (int)$m['team_id']);
        flash($warn ? 'warning' : 'success', 'Acta desada: ' . count($rows) . ' jugadors/es.' . $warn);
        redirect('/partits/' . $id);
    }

    /**
     * @param array<string,mixed>|null $m
     * @param array<string,string> $defaults
     */
    private function form(?array $m, array $defaults): void
    {
        $this->view('pages/matches/form', [
            'pageTitle'    => $m ? 'Edita el partit' : 'Nou partit',
            'activeNav'    => 'matches',
            'm'            => $m,
            'defaults'     => $defaults,
            'teams'        => TeamScope::teams(),
            'competitions' => MatchRepository::competitions(),
        ]);
        forget_input();
    }

    /**
     * @return array<string,mixed>
     */
    private function findOr404(int $id): array
    {
        $m = MatchRepository::find($id);
        if (!$m || !TeamScope::canAccess((int)$m['team_id'])) {
            throw new HttpException(404);
        }
        return $m;
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
}
