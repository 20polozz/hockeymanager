<?php
declare(strict_types=1);

namespace App\Import\Connectors;

use App\Core\Database;
use App\Core\TeamScope;
use App\Import\ConnectorInterface;

/**
 * Importació de jugadors/es des d'un fitxer CSV (per exemple, exportat d'Excel).
 * Columnes: nom; cognoms; data de naixement; posició; equip; dorsal
 */
final class CsvPlayersConnector implements ConnectorInterface
{
    public const MAX_BYTES = 1048576;
    public const MAX_ROWS = 500;

    public function key(): string { return 'csv-jugadors'; }
    public function name(): string { return 'Fitxer CSV de jugadors/es'; }
    public function description(): string { return 'Dona d\'alta jugadors/es a partir d\'un full de càlcul exportat com a CSV.'; }
    public function status(): array { return ['state' => 'disponible', 'note' => 'Llest per fer servir.']; }
    public function capabilities(): array { return ['Jugadors/es', 'Equips i dorsals']; }

    /**
     * Llegeix i valida el fitxer. Retorna les files amb errors marcats.
     * @return array<int,array<string,mixed>>
     */
    public function parse(string $content): array
    {
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        if (!$lines) {
            return [];
        }
        $sep = substr_count($lines[0], ';') >= substr_count($lines[0], ',') ? ';' : ',';

        $teams = [];
        foreach (TeamScope::teams() as $t) {
            $teams[$this->norm((string)$t['name'])] = (int)$t['id'];
        }

        $rows = [];
        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }
            $c = array_map('trim', str_getcsv($line, $sep));
            if ($i === 0 && preg_match('/^(nom|name|first)/i', $c[0] ?? '')) {
                continue; // capçalera
            }
            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
            $r = [
                'line'       => $i + 1,
                'first_name' => mb_substr($c[0] ?? '', 0, 60),
                'last_name'  => mb_substr($c[1] ?? '', 0, 100),
                'birth_date' => $this->date($c[2] ?? ''),
                'birth_raw'  => $c[2] ?? '',
                'position'   => preg_match('/^port/i', $c[3] ?? '') ? 'porter' : 'jugador',
                'team_name'  => $c[4] ?? '',
                'team_id'    => $teams[$this->norm($c[4] ?? '')] ?? 0,
                'dorsal'     => (isset($c[5]) && ctype_digit($c[5]) && (int)$c[5] <= 99) ? (int)$c[5] : null,
                'errors'     => [],
                'existing'   => 0,
            ];
            if ($r['first_name'] === '' || $r['last_name'] === '') {
                $r['errors'][] = 'Falta el nom o els cognoms';
            }
            if ($r['birth_raw'] !== '' && $r['birth_date'] === null) {
                $r['errors'][] = 'Data no vàlida';
            }
            if ($r['team_name'] === '' || $r['team_id'] === 0) {
                $r['errors'][] = 'Equip desconegut';
            }
            if (!$r['errors']) {
                $r['existing'] = (int)Database::value(
                    'SELECT id FROM players WHERE active = 1 AND first_name = ? AND last_name = ? LIMIT 1',
                    [$r['first_name'], $r['last_name']]
                );
            }
            $rows[] = $r;
        }
        return $rows;
    }

    /**
     * Desa les files vàlides. Si el jugador/a ja existeix, només l'afegeix a l'equip.
     * @param array<int,array<string,mixed>> $rows
     * @return array{created:int,added:int,skipped:int}
     */
    public function import(array $rows): array
    {
        $out = ['created' => 0, 'added' => 0, 'skipped' => 0];
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $r) {
            if ($r['errors'] || !TeamScope::canAccess((int)$r['team_id'])) {
                $out['skipped']++;
                continue;
            }
            $pid = (int)$r['existing'];
            if ($pid === 0) {
                Database::query(
                    'INSERT INTO players (first_name, last_name, birth_date, position, created_at) VALUES (?, ?, ?, ?, ?)',
                    [$r['first_name'], $r['last_name'], $r['birth_date'], $r['position'], $now]
                );
                $pid = (int)Database::pdo()->lastInsertId();
                $out['created']++;
            }
            $member = Database::value('SELECT 1 FROM team_players WHERE team_id = ? AND player_id = ? AND left_at IS NULL', [$r['team_id'], $pid]);
            if (!$member) {
                Database::query('INSERT INTO team_players (team_id, player_id, dorsal, joined_on) VALUES (?, ?, ?, ?)', [$r['team_id'], $pid, $r['dorsal'], date('Y-m-d')]);
                if ((int)$r['existing'] > 0) {
                    $out['added']++;
                }
            } elseif ((int)$r['existing'] > 0) {
                $out['skipped']++;
            }
        }
        return $out;
    }

    private function norm(string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s) ?? $s));
    }

    private function date(string $s): ?string
    {
        $s = trim($s);
        foreach (['!d/m/Y', '!Y-m-d', '!d-m-Y', '!d.m.Y'] as $f) {
            $d = \DateTime::createFromFormat($f, $s);
            if ($d && $d->format(substr($f, 1)) === $s && $d <= new \DateTime('today') && (int)$d->format('Y') >= 1940) {
                return $d->format('Y-m-d');
            }
        }
        return null;
    }
}
