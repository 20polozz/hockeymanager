<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CategoryRepository
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        return Database::all(
            'SELECT c.id, c.name, c.min_age, c.max_age, c.sort_order,
                    (SELECT COUNT(*) FROM teams t WHERE t.category_id = c.id) AS teams,
                    (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR \', \') FROM teams t WHERE t.category_id = c.id) AS team_names
             FROM categories c ORDER BY c.sort_order, c.name'
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function create(array $d): int
    {
        $sort = (int)Database::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM categories');
        Database::query('INSERT INTO categories (name, min_age, max_age, sort_order) VALUES (?, ?, ?, ?)', [$d['name'], $d['min_age'], $d['max_age'], $sort]);
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function update(int $id, array $d): void
    {
        Database::query('UPDATE categories SET name = ?, min_age = ?, max_age = ? WHERE id = ?', [$d['name'], $d['min_age'], $d['max_age'], $id]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM categories WHERE id = ?', [$id]);
    }

    public static function inUse(int $id): bool
    {
        return (bool)Database::value('SELECT 1 FROM teams WHERE category_id = ? LIMIT 1', [$id]);
    }

    /** Mou una categoria amunt (-1) o avall (+1). L'ordre també marca qui pot pujar de categoria. */
    public static function move(int $id, int $dir): void
    {
        $ids = array_map('intval', array_column(Database::all('SELECT id FROM categories ORDER BY sort_order, name'), 'id'));
        $i = array_search($id, $ids, true);
        $j = $i === false ? false : $i + $dir;
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $pos => $cid) {
            Database::query('UPDATE categories SET sort_order = ? WHERE id = ?', [($pos + 1) * 10, $cid]);
        }
    }

    /**
     * @param array<string,mixed> $in
     * @return array{0:array<string,mixed>,1:array<int,string>}
     */
    public static function validate(array $in, ?int $id): array
    {
        $age = function ($v): ?int {
            $v = trim((string)$v);
            return ctype_digit($v) && (int)$v <= 99 ? (int)$v : null;
        };
        $d = [
            'name'    => trim((string)($in['name'] ?? '')),
            'min_age' => $age($in['min_age'] ?? ''),
            'max_age' => $age($in['max_age'] ?? ''),
        ];
        $errors = [];
        if (mb_strlen($d['name']) < 2 || mb_strlen($d['name']) > 40) {
            $errors[] = 'El nom de la categoria ha de tenir entre 2 i 40 caràcters.';
        } elseif (Database::value('SELECT 1 FROM categories WHERE name = ? AND id <> ?', [$d['name'], $id ?? 0])) {
            $errors[] = 'Ja hi ha una categoria amb aquest nom.';
        }
        if ($d['min_age'] !== null && $d['max_age'] !== null && $d['min_age'] > $d['max_age']) {
            $errors[] = 'L\'edat mínima no pot ser més gran que la màxima.';
        }
        return [$d, $errors];
    }
}
