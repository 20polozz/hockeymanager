<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;
use App\Core\TeamScope;

/**
 * Sessions d'entrenament: una llista ordenada d'exercicis, opcionalment
 * lligada a un equip i a un entrenament del calendari.
 */
final class SessionRepository
{
    /**
     * Sessions visibles: dels equips de l'usuari o generals (sense equip).
     * @return array<int,array<string,mixed>>
     */
    public static function visible(int $teamFilter = 0): array
    {
        $ids = TeamScope::ids();
        $params = [];
        if ($teamFilter > 0) {
            $where = 's.team_id = ?';
            $params[] = $teamFilter;
        } else {
            $where = 's.team_id IS NULL' . ($ids ? ' OR s.team_id IN (' . Database::in($ids) . ')' : '');
            $params = $ids;
        }
        return Database::all(
            "SELECT s.id, s.title, s.team_id, s.event_id, s.created_at, t.name AS team_name, t.color,
                    e.starts_at AS event_at, u.name AS author,
                    COUNT(se.id) AS exercises,
                    COALESCE(SUM(COALESCE(se.duration_min, x.duration_min, 0)), 0) AS total_minutes
             FROM training_sessions s
             LEFT JOIN teams t ON t.id = s.team_id
             LEFT JOIN events e ON e.id = s.event_id
             LEFT JOIN users u ON u.id = s.created_by
             LEFT JOIN session_exercises se ON se.session_id = s.id
             LEFT JOIN exercises x ON x.id = se.exercise_id
             WHERE $where
             GROUP BY s.id, s.title, s.team_id, s.event_id, s.created_at, t.name, t.color, e.starts_at, u.name
             ORDER BY COALESCE(e.starts_at, s.created_at) DESC",
            $params
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        $s = Database::first(
            'SELECT s.*, t.name AS team_name, t.color, e.title AS event_title, e.starts_at AS event_at, u.name AS author
             FROM training_sessions s
             LEFT JOIN teams t ON t.id = s.team_id
             LEFT JOIN events e ON e.id = s.event_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.id = ?',
            [$id]
        );
        if (!$s) {
            return null;
        }
        $s['items'] = Database::all(
            'SELECT se.id, se.position, se.duration_min, se.notes, x.id AS exercise_id, x.name, x.category,
                    x.duration_min AS default_minutes, x.objective
             FROM session_exercises se JOIN exercises x ON x.id = se.exercise_id
             WHERE se.session_id = ? ORDER BY se.position, se.id',
            [$id]
        );
        $s['total_minutes'] = array_sum(array_map(function (array $i): int {
            return (int)($i['duration_min'] ?? $i['default_minutes'] ?? 0);
        }, $s['items']));
        return $s;
    }

    public static function canAccess(array $session): bool
    {
        return $session['team_id'] === null || TeamScope::canAccess((int)$session['team_id']);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forEvent(int $eventId): array
    {
        return Database::all(
            'SELECT s.id, s.title,
                    COALESCE(SUM(COALESCE(se.duration_min, x.duration_min, 0)), 0) AS total_minutes
             FROM training_sessions s
             LEFT JOIN session_exercises se ON se.session_id = s.id
             LEFT JOIN exercises x ON x.id = se.exercise_id
             WHERE s.event_id = ? GROUP BY s.id, s.title',
            [$eventId]
        );
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function create(array $d): int
    {
        Database::query(
            'INSERT INTO training_sessions (title, team_id, event_id, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$d['title'], $d['team_id'] ?: null, $d['event_id'] ?: null, $d['notes'] ?: null, Auth::id() ?: null, date('Y-m-d H:i:s')]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE training_sessions SET title = ?, team_id = ?, event_id = ?, notes = ? WHERE id = ?',
            [$d['title'], $d['team_id'] ?: null, $d['event_id'] ?: null, $d['notes'] ?: null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM training_sessions WHERE id = ?', [$id]);
    }

    public static function addExercise(int $sessionId, int $exerciseId): void
    {
        $pos = (int)Database::value('SELECT COALESCE(MAX(position), 0) + 1 FROM session_exercises WHERE session_id = ?', [$sessionId]);
        Database::query('INSERT INTO session_exercises (session_id, exercise_id, position) VALUES (?, ?, ?)', [$sessionId, $exerciseId, $pos]);
    }

    public static function updateItem(int $sessionId, int $itemId, ?int $minutes, string $notes): void
    {
        Database::query(
            'UPDATE session_exercises SET duration_min = ?, notes = ? WHERE id = ? AND session_id = ?',
            [$minutes, $notes !== '' ? $notes : null, $itemId, $sessionId]
        );
    }

    public static function removeItem(int $sessionId, int $itemId): void
    {
        Database::query('DELETE FROM session_exercises WHERE id = ? AND session_id = ?', [$itemId, $sessionId]);
        self::renumber($sessionId);
    }

    /** Mou un exercici una posició amunt (-1) o avall (+1). */
    public static function move(int $sessionId, int $itemId, int $dir): void
    {
        self::renumber($sessionId);
        $ids = array_map('intval', array_column(Database::all('SELECT id FROM session_exercises WHERE session_id = ? ORDER BY position, id', [$sessionId]), 'id'));
        $i = array_search($itemId, $ids, true);
        $j = $i === false ? false : $i + $dir;
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $pos => $id) {
            Database::query('UPDATE session_exercises SET position = ? WHERE id = ?', [$pos + 1, $id]);
        }
    }

    private static function renumber(int $sessionId): void
    {
        $ids = array_column(Database::all('SELECT id FROM session_exercises WHERE session_id = ? ORDER BY position, id', [$sessionId]), 'id');
        foreach ($ids as $pos => $id) {
            Database::query('UPDATE session_exercises SET position = ? WHERE id = ?', [$pos + 1, $id]);
        }
    }

    /**
     * Entrenaments propers (i dels últims dies) per vincular-hi una sessió.
     * @return array<int,array<string,mixed>>
     */
    public static function linkableEvents(): array
    {
        $ids = TeamScope::ids();
        if (!$ids) {
            return [];
        }
        return Database::all(
            "SELECT e.id, e.title, e.starts_at, e.team_id, t.name AS team_name
             FROM events e JOIN teams t ON t.id = e.team_id
             WHERE e.type = 'entrenament' AND e.team_id IN (" . Database::in($ids) . ") AND e.starts_at >= ? AND e.starts_at < ?
             ORDER BY e.starts_at",
            array_merge($ids, [date('Y-m-d 00:00:00', strtotime('-7 days')), date('Y-m-d 00:00:00', strtotime('+45 days'))])
        );
    }

    /**
     * @param array<string,mixed> $in
     * @return array{0:array<string,mixed>,1:array<int,string>}
     */
    public static function validate(array $in): array
    {
        $d = [
            'title'    => trim((string)($in['title'] ?? '')),
            'team_id'  => (int)($in['team_id'] ?? 0),
            'event_id' => (int)($in['event_id'] ?? 0),
            'notes'    => mb_substr(trim((string)($in['notes'] ?? '')), 0, 2000),
        ];
        $errors = [];
        if (mb_strlen($d['title']) < 2 || mb_strlen($d['title']) > 120) {
            $errors[] = 'Escriu un títol (2-120 caràcters).';
        }
        if ($d['team_id'] > 0 && !TeamScope::canAccess($d['team_id'])) {
            $errors[] = 'No tens accés a aquest equip.';
        }
        if ($d['event_id'] > 0) {
            $ev = Database::first("SELECT team_id FROM events WHERE id = ? AND type = 'entrenament'", [$d['event_id']]);
            if (!$ev || !TeamScope::canAccess((int)$ev['team_id'])) {
                $errors[] = 'L\'entrenament triat no és vàlid.';
            } elseif ($d['team_id'] === 0) {
                $d['team_id'] = (int)$ev['team_id'];
            }
        }
        return [$d, $errors];
    }
}
