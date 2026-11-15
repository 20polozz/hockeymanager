<?php
declare(strict_types=1);

namespace App\Database;

use App\Core\Database;
use App\Repositories\Settings;

/**
 * Dades de DEMOSTRACIÓ (fictícies). Només es carreguen si es marca l'opció a
 * l'instal·lador, i mentre hi siguin l'app mostra un avís a totes les pàgines.
 * Les dates es generen al voltant del dia d'avui perquè el dashboard tingui sentit.
 */
final class DemoSeeder
{
    private const FIRST = ['Pau', 'Marc', 'Jan', 'Arnau', 'Pol', 'Nil', 'Biel', 'Martí', 'Oriol', 'Roger', 'Èric', 'Aleix', 'Gerard', 'Joel', 'Adrià', 'Hugo', 'Max', 'Quim', 'Bernat', 'Lluc', 'Abril', 'Júlia', 'Laia', 'Mar', 'Ona', 'Carla', 'Aina', 'Berta', 'Clàudia', 'Paula'];
    private const LAST = ['Puig', 'Soler', 'Vila', 'Font', 'Serra', 'Roca', 'Pujol', 'Casals', 'Ferrer', 'Torres', 'Bosch', 'Mas', 'Riera', 'Sala', 'Vidal', 'Camps', 'Prat', 'Coll', 'Valls', 'Badia', 'Costa', 'Planas', 'Rius', 'Comas'];
    private const RIVALS = ['CP Vic', 'CE Noia', 'CH Caldes', 'CP Manlleu', 'Reus Deportiu', 'CP Voltregà', 'HC Sentmenat', 'CP Calafell', 'CE Lleida', 'CP Tordera', 'CH Mataró', 'CP Alpicat'];

    /**
     * Crea les dades i retorna la contrasenya dels comptes de demostració.
     */
    public static function run(int $coordinatorId): string
    {
        mt_srand(2026);
        $now = time();
        $pdo = Database::pdo();
        $demoPassword = 'demo-' . bin2hex(random_bytes(4));
        $hash = password_hash($demoPassword, PASSWORD_DEFAULT);
        $created = date('Y-m-d H:i:s');

        // Temporada actual (setembre → juny)
        $y = (int)date('Y', $now);
        $startYear = ((int)date('n', $now) >= 8) ? $y : $y - 1;
        $seasonId = self::insert('seasons', [
            'name' => $startYear . '-' . substr((string)($startYear + 1), 2),
            'starts_on' => $startYear . '-09-01',
            'ends_on' => ($startYear + 1) . '-06-30',
            'is_current' => 1,
        ]);

        $cat = [];
        foreach (Database::all('SELECT id, name FROM categories') as $c) {
            $cat[$c['name']] = (int)$c['id'];
        }

        $teamsDef = [
            ['Sènior A', 'Sènior', '#1e3a8a', 'Lliga Nacional Catalana', [20, 32]],
            ['Júnior', 'Júnior', '#0f766e', 'Lliga Catalana Júnior', [17, 19]],
            ['Juvenil', 'Juvenil', '#7c3aed', 'Lliga Catalana Juvenil', [15, 16]],
            ['Infantil', 'Infantil', '#c2410c', 'Lliga Catalana Infantil', [13, 14]],
            ['Aleví', 'Aleví', '#be123c', 'Lliga Territorial Aleví', [11, 12]],
        ];

        $coachNames = ['Jordi Sabaté', 'Núria Pons', 'Xavier Miró', 'Anna Bertran', 'Sergi Llobet'];
        $teams = [];
        foreach ($teamsDef as $i => [$name, $category, $color, $competition, $ages]) {
            $teamId = self::insert('teams', [
                'season_id' => $seasonId, 'category_id' => $cat[$category] ?? null,
                'name' => $name, 'color' => $color, 'created_at' => $created,
            ]);
            $coachId = self::insert('users', [
                'name' => $coachNames[$i] . ' (demo)',
                'email' => 'entrenador' . ($i + 1) . '@demo.hockeymanager',
                'password_hash' => $hash, 'role' => 'entrenador', 'created_at' => $created,
            ]);
            $pdo->prepare('INSERT INTO team_staff (team_id, user_id, staff_role) VALUES (?, ?, ?)')->execute([$teamId, $coachId, 'principal']);

            // 10 jugadors per equip (2 porters)
            $players = [];
            $dorsals = array_values(array_diff(range(2, 30), [13])); // 1 i 13 per als porters
            shuffle($dorsals);
            for ($p = 0; $p < 10; $p++) {
                $age = mt_rand($ages[0], $ages[1]);
                $birth = date('Y-m-d', mktime(0, 0, 0, mt_rand(1, 12), mt_rand(1, 28), (int)date('Y', $now) - $age));
                $playerId = self::insert('players', [
                    'first_name' => self::FIRST[mt_rand(0, count(self::FIRST) - 1)],
                    'last_name' => self::LAST[mt_rand(0, count(self::LAST) - 1)] . ' ' . self::LAST[mt_rand(0, count(self::LAST) - 1)],
                    'birth_date' => $birth,
                    'position' => $p < 2 ? 'porter' : 'jugador',
                    'created_at' => $created,
                ]);
                self::insert('team_players', [
                    'team_id' => $teamId, 'player_id' => $playerId,
                    'dorsal' => $p < 2 ? ($p === 0 ? 1 : 13) : $dorsals[$p],
                    'joined_on' => $startYear . '-09-01',
                ]);
                $players[] = $playerId;
            }
            $teams[] = ['id' => $teamId, 'coach' => $coachId, 'players' => $players, 'competition' => $competition, 'name' => $name];
        }

        // Compte de jugador de demostració (primer jugador de camp del Júnior)
        $demoPlayerId = $teams[1]['players'][2];
        self::insert('users', [
            'name' => self::playerName($demoPlayerId) . ' (demo)',
            'email' => 'jugador@demo.hockeymanager',
            'password_hash' => $hash, 'role' => 'jugador', 'player_id' => $demoPlayerId, 'created_at' => $created,
        ]);

        $statusPool = array_merge(array_fill(0, 17, 'present'), ['absent', 'absent', 'justificat', 'retard', 'retard']);

        foreach ($teams as $ti => $team) {
            // Entrenaments: dimarts i dijous (equips grans) o dilluns i dimecres, de -6 a +2 setmanes
            $days = $ti < 2 ? [2, 4] : [1, 3];
            $hour = 17 + $ti % 4;
            $monday = strtotime('monday this week', $now);
            for ($week = -6; $week <= 2; $week++) {
                foreach ($days as $dow) {
                    $start = strtotime(sprintf('%+d days', ($week * 7) + $dow - 1), $monday) + $hour * 3600 + 30 * 60;
                    $eventId = self::insert('events', [
                        'team_id' => $team['id'], 'type' => 'entrenament',
                        'title' => 'Entrenament ' . $team['name'],
                        'starts_at' => date('Y-m-d H:i:s', $start),
                        'ends_at' => date('Y-m-d H:i:s', $start + 90 * 60),
                        'location' => 'Pavelló Municipal — Pista 1',
                        'created_by' => $team['coach'], 'created_at' => $created,
                    ]);
                    if ($start < $now - 3600) {
                        foreach ($team['players'] as $playerId) {
                            $status = $statusPool[mt_rand(0, count($statusPool) - 1)];
                            self::insert('attendance', [
                                'event_id' => $eventId, 'player_id' => $playerId, 'status' => $status,
                                'observation' => $status === 'justificat' ? 'Examen a l\'institut' : null,
                                'recorded_by' => $team['coach'],
                                'recorded_at' => date('Y-m-d H:i:s', $start + 100 * 60),
                            ]);
                        }
                    }
                }
            }

            // Partits: dissabtes, de -5 a +3 setmanes
            $saturday = strtotime('saturday this week', $now);
            $rivals = self::RIVALS;
            shuffle($rivals);
            for ($week = -5; $week <= 3; $week++) {
                $start = strtotime(sprintf('%+d weeks', $week), $saturday) + (10 + $ti * 2) * 3600;
                $played = $start < $now;
                $home = ($week % 2) === 0;
                $matchId = self::insert('matches', [
                    'team_id' => $team['id'], 'season_id' => $seasonId,
                    'opponent' => $rivals[($week + 5) % count($rivals)],
                    'competition' => $team['competition'],
                    'starts_at' => date('Y-m-d H:i:s', $start),
                    'location' => $home ? 'Pavelló Municipal' : 'Pista rival',
                    'is_home' => $home ? 1 : 0,
                    'goals_for' => $played ? mt_rand(0, 7) : null,
                    'goals_against' => $played ? mt_rand(0, 6) : null,
                    'status' => $played ? 'jugat' : 'programat',
                    'created_by' => $team['coach'], 'created_at' => $created,
                ]);

                // Convocatòria del proper partit amb respostes variades
                if (!$played && $start - $now < 9 * 86400) {
                    $callupId = self::insert('callups', [
                        'match_id' => $matchId,
                        'message' => 'Convocatòria per al partit contra ' . $rivals[($week + 5) % count($rivals)] . '. Cal ser-hi 45 minuts abans.',
                        'meeting_at' => date('Y-m-d H:i:s', $start - 45 * 60),
                        'sent_at' => date('Y-m-d H:i:s', $now - 86400),
                        'created_by' => $team['coach'], 'created_at' => $created,
                    ]);
                    foreach (array_slice($team['players'], 0, 9) as $k => $playerId) {
                        $resp = $k < 5 ? 'confirmat' : ($k === 5 ? 'rebutjat' : 'pendent');
                        if ($playerId === $demoPlayerId) {
                            $resp = 'pendent';
                        }
                        self::insert('callup_players', [
                            'callup_id' => $callupId, 'player_id' => $playerId, 'response' => $resp,
                            'responded_at' => $resp === 'pendent' ? null : date('Y-m-d H:i:s', $now - mt_rand(1, 20) * 3600),
                        ]);
                    }
                }
            }
        }

        // Activitat recent
        $acts = [
            [$teams[0], 'attendance', 'Ha passat llista de l\'entrenament del Sènior A', 2],
            [$teams[1], 'callup', 'Ha enviat la convocatòria del Júnior', 22],
            [$teams[3], 'match', 'Ha registrat el resultat del partit de l\'Infantil', 30],
            [$teams[2], 'exercise', 'Ha creat l\'exercici "Rondo 4x2 amb pressió"', 50],
            [$teams[4], 'attendance', 'Ha passat llista de l\'entrenament de l\'Aleví', 70],
            [$teams[0], 'match', 'Ha programat un partit amistós del Sènior A', 96],
        ];
        foreach ($acts as [$team, $type, $desc, $hoursAgo]) {
            self::insert('activity_log', [
                'user_id' => $team['coach'], 'team_id' => $team['id'], 'type' => $type,
                'description' => $desc, 'created_at' => date('Y-m-d H:i:s', $now - $hoursAgo * 3600),
            ]);
        }
        self::insert('activity_log', [
            'user_id' => $coordinatorId, 'team_id' => null, 'type' => 'system',
            'description' => 'Ha carregat les dades de demostració', 'created_at' => date('Y-m-d H:i:s', $now - 60),
        ]);

        Settings::set('demo_data', '1');
        return $demoPassword;
    }

    private static function playerName(int $id): string
    {
        $p = Database::first('SELECT first_name, last_name FROM players WHERE id = ?', [$id]);
        return $p ? $p['first_name'] . ' ' . $p['last_name'] : 'Jugador';
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function insert(string $table, array $row): int
    {
        $cols = array_keys($row);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . Database::in($cols) . ')';
        Database::query($sql, array_values($row));
        return (int)Database::pdo()->lastInsertId();
    }
}
