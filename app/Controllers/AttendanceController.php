<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\AttendanceRepository;
use App\Repositories\EventRepository;

final class AttendanceController extends Controller
{
    /** Llista d'entrenaments recents i propers per passar llista. */
    public function index(): void
    {
        $now = new \DateTime();
        $trainings = EventRepository::trainings(
            team_filter_ids(),
            (clone $now)->modify('-35 days')->format('Y-m-d 00:00:00'),
            (clone $now)->modify('+8 days')->format('Y-m-d 00:00:00')
        );
        $upcoming = [];
        $past = [];
        foreach ($trainings as $t) {
            // Avui (o en curs) es considera "per passar llista"
            if (substr((string)$t['starts_at'], 0, 10) > $now->format('Y-m-d')) {
                $upcoming[] = $t;
            } else {
                $past[] = $t;
            }
        }
        $this->view('pages/attendance/index', [
            'pageTitle' => 'Assistència',
            'activeNav' => 'attendance',
            'teams'     => TeamScope::teams(),
            'team'      => team_filter(),
            'upcoming'  => array_reverse($upcoming),
            'past'      => $past,
        ]);
    }

    /** Pantalla per passar llista (pensada per al mòbil). */
    public function take(int $eventId): void
    {
        $event = $this->training($eventId);
        $this->view('pages/attendance/take', [
            'pageTitle' => 'Passar llista',
            'activeNav' => 'attendance',
            'event'     => $event,
            'sheet'     => $sheet = AttendanceRepository::sheet($event),
            'away'      => \App\Repositories\AbsenceRepository::onDate(substr((string)$event['starts_at'], 0, 10), array_column($sheet, 'id')),
        ]);
    }

    public function save(int $eventId): void
    {
        $event = $this->training($eventId);
        $allowed = array_map(function (array $r): int {
            return (int)$r['id'];
        }, AttendanceRepository::sheet($event));

        $statuses = is_array($_POST['status'] ?? null) ? $_POST['status'] : [];
        $notes = is_array($_POST['observation'] ?? null) ? $_POST['observation'] : [];
        $rows = [];
        foreach ($allowed as $pid) {
            $st = (string)($statuses[$pid] ?? '');
            $rows[$pid] = [
                'status'      => array_key_exists($st, AttendanceRepository::STATUSES) ? $st : '',
                'observation' => mb_substr(trim((string)($notes[$pid] ?? '')), 0, 255),
            ];
        }
        $saved = AttendanceRepository::save($eventId, $rows);
        ActivityLog::add('attendance', 'Ha passat llista de «' . $event['title'] . '» (' . date('d/m', strtotime((string)$event['starts_at'])) . ')', $event['team_id'] !== null ? (int)$event['team_id'] : null);
        flash('success', 'Llista desada: ' . $saved . ' jugadors/es marcats.');
        redirect('/assistencia');
    }

    public function stats(): void
    {
        $period = in_array($_GET['periode'] ?? '', ['30', '90', 'temporada'], true) ? (string)$_GET['periode'] : '30';
        $to = (new \DateTime('tomorrow'))->format('Y-m-d 00:00:00');
        if ($period === 'temporada') {
            $from = (string)(\App\Core\Database::value('SELECT starts_on FROM seasons WHERE is_current = 1 LIMIT 1') ?: date('Y-m-d', strtotime('-1 year'))) . ' 00:00:00';
        } else {
            $from = (new \DateTime('today'))->modify('-' . $period . ' days')->format('Y-m-d 00:00:00');
        }
        $ids = team_filter_ids();
        $players = AttendanceRepository::byPlayer($ids, $from, $to);
        $mostAbsent = array_values(array_filter($players, function (array $p): bool {
            return (int)$p['absent'] + (int)$p['justificat'] > 0;
        }));
        usort($mostAbsent, function (array $a, array $b): int {
            return ((int)$b['absent'] * 2 + (int)$b['justificat']) <=> ((int)$a['absent'] * 2 + (int)$a['justificat']);
        });

        $this->view('pages/attendance/stats', [
            'pageTitle'  => 'Estadístiques d\'assistència',
            'activeNav'  => 'attendance',
            'teams'      => TeamScope::teams(),
            'team'       => team_filter(),
            'period'     => $period,
            'players'    => $players,
            'byTeam'     => AttendanceRepository::byTeam($ids, $from, $to),
            'monthly'    => AttendanceRepository::monthly($ids, 6),
            'mostAbsent' => array_slice($mostAbsent, 0, 5),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function training(int $id): array
    {
        $event = EventRepository::find($id);
        if (!$event || !in_array($event['type'], EventRepository::TRAINING_TYPES, true) || !EventRepository::canAccess($event)) {
            throw new HttpException(404);
        }
        return $event;
    }
}
