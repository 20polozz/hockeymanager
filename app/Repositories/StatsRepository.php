<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Estadístiques calculades NOMÉS a partir de dades registrades:
 * resultats dels partits, actes (match_players) i llistes d'assistència.
 * Les dades de porteria que no s'han anotat (NULL) no compten.
 */
final class StatsRepository
{
    /**
     * Condició de temporada (0 = totes) sobre l'àlies de matches indicat.
     * @return array{0:string,1:array<int,int>}
     */
    private static function season(string $alias, int $seasonId): array
    {
        return $seasonId > 0 ? [" AND $alias.season_id = ?", [$seasonId]] : ['', []];
    }

    /**
     * Balanç per equip: partits, V/E/D, gols i ratxa dels últims 5.
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function teamSummary(array $teamIds, int $seasonId): array
    {
        if (!$teamIds) {
            return [];
        }
        [$sw, $sp] = self::season('m', $seasonId);
        $rows = Database::all(
            "SELECT t.id, t.name, t.color,
                    COUNT(m.id) AS played,
                    SUM(m.goals_for > m.goals_against) AS won,
                    SUM(m.goals_for = m.goals_against) AS drawn,
                    SUM(m.goals_for < m.goals_against) AS lost,
                    COALESCE(SUM(m.goals_for), 0) AS gf,
                    COALESCE(SUM(m.goals_against), 0) AS ga
             FROM teams t
             LEFT JOIN matches m ON m.team_id = t.id AND m.status = 'jugat'$sw
             WHERE t.id IN (" . Database::in($teamIds) . ")
             GROUP BY t.id, t.name, t.color ORDER BY t.name",
            array_merge($sp, $teamIds)
        );
        foreach ($rows as &$r) {
            $form = Database::all(
                "SELECT goals_for, goals_against FROM matches m WHERE m.team_id = ? AND m.status = 'jugat'$sw ORDER BY m.starts_at DESC LIMIT 5",
                array_merge([$r['id']], $sp)
            );
            $r['form'] = array_reverse(array_map(function (array $f): string {
                return (int)$f['goals_for'] > (int)$f['goals_against'] ? 'V' : ((int)$f['goals_for'] < (int)$f['goals_against'] ? 'D' : 'E');
            }, $form));
        }
        return $rows;
    }

    /**
     * Resultats en ordre cronològic (per al gràfic d'evolució).
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function results(array $teamIds, int $seasonId, int $limit = 12): array
    {
        if (!$teamIds) {
            return [];
        }
        [$sw, $sp] = self::season('m', $seasonId);
        return array_reverse(Database::all(
            "SELECT m.id, m.opponent, m.starts_at, m.goals_for, m.goals_against, m.is_home, t.name AS team_name
             FROM matches m JOIN teams t ON t.id = m.team_id
             WHERE m.status = 'jugat' AND m.team_id IN (" . Database::in($teamIds) . ")$sw
             ORDER BY m.starts_at DESC LIMIT ?",
            array_merge($teamIds, $sp, [$limit])
        ));
    }

    /**
     * Estadístiques individuals a partir de les actes.
     * @param array<int,int> $teamIds
     * @param int|null $onlyPlayer limitar a un jugador/a (vista de jugador)
     * @return array<int,array<string,mixed>>
     */
    public static function players(array $teamIds, int $seasonId, ?int $onlyPlayer = null): array
    {
        if (!$teamIds) {
            return [];
        }
        [$sw, $sp] = self::season('m', $seasonId);
        $params = array_merge($teamIds, $sp);
        $extra = '';
        if ($onlyPlayer !== null) {
            $extra = ' AND p.id = ?';
            $params[] = $onlyPlayer;
        }
        $rows = Database::all(
            "SELECT p.id, p.first_name, p.last_name, p.position,
                    COUNT(mp.match_id) AS played,
                    SUM(mp.goals) AS goals, SUM(mp.assists) AS assists,
                    SUM(mp.blue_cards) AS blue_cards, SUM(mp.red_cards) AS red_cards
             FROM match_players mp
             JOIN matches m ON m.id = mp.match_id AND m.status = 'jugat'
             JOIN players p ON p.id = mp.player_id
             WHERE m.team_id IN (" . Database::in($teamIds) . ")$sw$extra
             GROUP BY p.id, p.first_name, p.last_name, p.position
             ORDER BY goals DESC, assists DESC, played DESC, p.last_name",
            $params
        );
        return $rows;
    }

    /**
     * Estadístiques de porteria (només dades anotades).
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function goalkeepers(array $teamIds, int $seasonId, ?int $onlyPlayer = null): array
    {
        if (!$teamIds) {
            return [];
        }
        [$sw, $sp] = self::season('m', $seasonId);
        $params = array_merge($teamIds, $sp);
        $extra = '';
        if ($onlyPlayer !== null) {
            $extra = ' AND p.id = ?';
            $params[] = $onlyPlayer;
        }
        $rows = Database::all(
            "SELECT p.id, p.first_name, p.last_name,
                    COUNT(mp.match_id) AS played,
                    SUM(mp.gk_goals_against IS NOT NULL) AS ga_matches,
                    SUM(mp.gk_goals_against) AS goals_against,
                    SUM(mp.gk_saves) AS saves,
                    SUM(mp.gk_shots) AS shots,
                    SUM(CASE WHEN mp.gk_saves IS NOT NULL AND mp.gk_goals_against IS NOT NULL THEN mp.gk_saves END) AS pct_saves,
                    SUM(CASE WHEN mp.gk_saves IS NOT NULL AND mp.gk_goals_against IS NOT NULL THEN mp.gk_goals_against END) AS pct_ga,
                    SUM(mp.gk_penalties_faced) AS pen_faced, SUM(mp.gk_penalties_saved) AS pen_saved,
                    SUM(mp.gk_free_hits_faced) AS fd_faced, SUM(mp.gk_free_hits_saved) AS fd_saved
             FROM match_players mp
             JOIN matches m ON m.id = mp.match_id AND m.status = 'jugat'
             JOIN players p ON p.id = mp.player_id AND p.position = 'porter'
             WHERE m.team_id IN (" . Database::in($teamIds) . ")$sw$extra
             GROUP BY p.id, p.first_name, p.last_name
             ORDER BY played DESC, p.last_name",
            $params
        );
        foreach ($rows as &$r) {
            $den = (int)$r['pct_saves'] + (int)$r['pct_ga'];
            $r['save_pct'] = ($r['pct_saves'] !== null && $den > 0) ? round((int)$r['pct_saves'] / $den * 100, 1) : null;
            $r['gaa'] = (int)$r['ga_matches'] > 0 ? round((int)$r['goals_against'] / (int)$r['ga_matches'], 2) : null;
        }
        return $rows;
    }

    /**
     * Assistència als entrenaments per jugador/a (mapa id => %).
     * @param array<int,int> $teamIds
     * @return array<int,int|null>
     */
    public static function trainingAttendance(array $teamIds, int $seasonId): array
    {
        if (!$teamIds) {
            return [];
        }
        [$scopeW, $params] = AttendanceRepository::scope($teamIds);
        $where = '';
        if ($seasonId > 0) {
            $s = Database::first('SELECT starts_on, ends_on FROM seasons WHERE id = ?', [$seasonId]);
            if ($s) {
                $where = ' AND e.starts_at BETWEEN ? AND ?';
                $params[] = $s['starts_on'] . ' 00:00:00';
                $params[] = $s['ends_on'] . ' 23:59:59';
            }
        }
        $out = [];
        foreach (Database::all(
            "SELECT a.player_id, COUNT(*) AS total, SUM(a.status IN ('present','retard')) AS attended
             FROM attendance a JOIN events e ON e.id = a.event_id
             WHERE $scopeW$where
             GROUP BY a.player_id",
            $params
        ) as $r) {
            $out[(int)$r['player_id']] = (int)$r['total'] > 0 ? (int)round((int)$r['attended'] / (int)$r['total'] * 100) : null;
        }
        return $out;
    }

    /**
     * Totals d'un jugador/a per temporada (fitxa).
     * @return array<int,array<string,mixed>>
     */
    public static function playerBySeason(int $playerId): array
    {
        return Database::all(
            "SELECT s.name AS season, COUNT(mp.match_id) AS played, SUM(mp.goals) AS goals, SUM(mp.assists) AS assists,
                    SUM(mp.blue_cards) AS blue_cards, SUM(mp.red_cards) AS red_cards,
                    SUM(mp.gk_saves) AS saves, SUM(mp.gk_goals_against) AS goals_against,
                    SUM(CASE WHEN mp.gk_saves IS NOT NULL AND mp.gk_goals_against IS NOT NULL THEN mp.gk_saves END) AS pct_saves,
                    SUM(CASE WHEN mp.gk_saves IS NOT NULL AND mp.gk_goals_against IS NOT NULL THEN mp.gk_goals_against END) AS pct_ga
             FROM match_players mp
             JOIN matches m ON m.id = mp.match_id AND m.status = 'jugat'
             JOIN seasons s ON s.id = m.season_id
             WHERE mp.player_id = ?
             GROUP BY s.id, s.name ORDER BY s.starts_on DESC",
            [$playerId]
        );
    }
}
