<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

final class ExerciseRepository
{
    public const CATEGORIES = [
        'patinatge' => ['Patinatge', 'blue'],
        'control'   => ['Control de bola', 'teal'],
        'passades'  => ['Passades', 'green'],
        'xuts'      => ['Xuts', 'red'],
        'defensa'   => ['Defensa', 'navy'],
        'tactica'   => ['Tàctica', 'violet'],
        'fisica'    => ['Preparació física', 'orange'],
        'porters'   => ['Porters', 'amber'],
    ];
    public const DIFFICULTY = [1 => 'Fàcil', 2 => 'Mitjana', 3 => 'Difícil'];

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function search(string $q, string $category, bool $onlyFavorites): array
    {
        $where = ['1 = 1'];
        $params = [Auth::id()];
        if ($q !== '') {
            $where[] = '(e.name LIKE ? OR e.objective LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (isset(self::CATEGORIES[$category])) {
            $where[] = 'e.category = ?';
            $params[] = $category;
        }
        if ($onlyFavorites) {
            $where[] = 'f.user_id IS NOT NULL';
        }
        return Database::all(
            'SELECT e.id, e.name, e.category, e.objective, e.difficulty, e.duration_min, e.recommended_age, e.media_url,
                    (f.user_id IS NOT NULL) AS favorite
             FROM exercises e
             LEFT JOIN exercise_favorites f ON f.exercise_id = e.id AND f.user_id = ?
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY favorite DESC, e.name',
            $params
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT e.*, u.name AS author, EXISTS(SELECT 1 FROM exercise_favorites f WHERE f.exercise_id = e.id AND f.user_id = ?) AS favorite
             FROM exercises e LEFT JOIN users u ON u.id = e.created_by WHERE e.id = ?',
            [Auth::id(), $id]
        );
    }

    /**
     * Per al selector de les sessions.
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return Database::all('SELECT id, name, category, duration_min FROM exercises ORDER BY category, name');
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function create(array $d): int
    {
        Database::query(
            'INSERT INTO exercises (name, category, description, objective, difficulty, duration_min, material, media_url, recommended_age, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['name'], $d['category'], $d['description'] ?: null, $d['objective'] ?: null, $d['difficulty'], $d['duration_min'],
             $d['material'] ?: null, $d['media_url'] ?: null, $d['recommended_age'] ?: null, Auth::id() ?: null, date('Y-m-d H:i:s')]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE exercises SET name = ?, category = ?, description = ?, objective = ?, difficulty = ?, duration_min = ?,
                    material = ?, media_url = ?, recommended_age = ?, updated_at = ? WHERE id = ?',
            [$d['name'], $d['category'], $d['description'] ?: null, $d['objective'] ?: null, $d['difficulty'], $d['duration_min'],
             $d['material'] ?: null, $d['media_url'] ?: null, $d['recommended_age'] ?: null, date('Y-m-d H:i:s'), $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM exercises WHERE id = ?', [$id]);
    }

    public static function usageCount(int $id): int
    {
        return (int)Database::value('SELECT COUNT(DISTINCT session_id) FROM session_exercises WHERE exercise_id = ?', [$id]);
    }

    public static function toggleFavorite(int $id): bool
    {
        $uid = Auth::id();
        if (Database::value('SELECT 1 FROM exercise_favorites WHERE user_id = ? AND exercise_id = ?', [$uid, $id])) {
            Database::query('DELETE FROM exercise_favorites WHERE user_id = ? AND exercise_id = ?', [$uid, $id]);
            return false;
        }
        Database::query('INSERT INTO exercise_favorites (user_id, exercise_id) VALUES (?, ?)', [$uid, $id]);
        return true;
    }

    /**
     * @param array<string,mixed> $in
     * @return array{0:array<string,mixed>,1:array<int,string>}
     */
    public static function validate(array $in): array
    {
        $dur = trim((string)($in['duration_min'] ?? ''));
        $d = [
            'name'            => trim((string)($in['name'] ?? '')),
            'category'        => (string)($in['category'] ?? ''),
            'description'     => mb_substr(trim((string)($in['description'] ?? '')), 0, 5000),
            'objective'       => mb_substr(trim((string)($in['objective'] ?? '')), 0, 255),
            'difficulty'      => max(1, min(3, (int)($in['difficulty'] ?? 2))),
            'duration_min'    => ctype_digit($dur) ? (int)$dur : null,
            'material'        => mb_substr(trim((string)($in['material'] ?? '')), 0, 255),
            'media_url'       => trim((string)($in['media_url'] ?? '')),
            'recommended_age' => mb_substr(trim((string)($in['recommended_age'] ?? '')), 0, 60),
        ];
        $errors = [];
        if (mb_strlen($d['name']) < 2 || mb_strlen($d['name']) > 120) {
            $errors[] = 'El nom ha de tenir entre 2 i 120 caràcters.';
        }
        if (!isset(self::CATEGORIES[$d['category']])) {
            $errors[] = 'Tria una categoria.';
        }
        if ($dur !== '' && ($d['duration_min'] === null || $d['duration_min'] < 1 || $d['duration_min'] > 240)) {
            $errors[] = 'La durada ha de ser un nombre de minuts entre 1 i 240.';
        }
        if ($d['media_url'] !== '' && (!filter_var($d['media_url'], FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $d['media_url']) || strlen($d['media_url']) > 500)) {
            $errors[] = 'L\'enllaç de la imatge o el vídeo ha de començar per https://';
        }
        return [$d, $errors];
    }

    /**
     * Com mostrar l'enllaç multimèdia: ['type' => 'youtube'|'image'|'link', 'src' => ...].
     * @return array{type:string,src:string}|null
     */
    public static function media(?string $url): ?array
    {
        if (!$url) {
            return null;
        }
        if (preg_match('#^https://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return ['type' => 'youtube', 'src' => 'https://www.youtube-nocookie.com/embed/' . $m[1]];
        }
        if (preg_match('#\.(?:jpe?g|png|gif|webp)(?:\?.*)?$#i', $url)) {
            return ['type' => 'image', 'src' => $url];
        }
        return ['type' => 'link', 'src' => $url];
    }
}
