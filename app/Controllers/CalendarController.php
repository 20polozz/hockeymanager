<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Gate;
use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\EventRepository;

final class CalendarController extends Controller
{
    private const VIEWS = ['mes', 'setmana', 'dia'];

    public function index(): void
    {
        $view = in_array($_GET['vista'] ?? '', self::VIEWS, true) ? (string)$_GET['vista'] : 'mes';
        $date = \DateTime::createFromFormat('!Y-m-d', (string)($_GET['data'] ?? '')) ?: new \DateTime('today');
        $date->setTime(0, 0);

        switch ($view) {
            case 'dia':
                $from = clone $date;
                $to = (clone $date)->modify('+1 day');
                $prev = (clone $date)->modify('-1 day');
                $next = (clone $date)->modify('+1 day');
                break;
            case 'setmana':
                $from = (clone $date)->modify('-' . ((int)$date->format('N') - 1) . ' days');
                $to = (clone $from)->modify('+7 days');
                $prev = (clone $from)->modify('-7 days');
                $next = (clone $from)->modify('+7 days');
                break;
            default:
                $first = (clone $date)->modify('first day of this month');
                $from = (clone $first)->modify('-' . ((int)$first->format('N') - 1) . ' days');
                $last = (clone $date)->modify('last day of this month');
                $to = (clone $last)->modify('+' . (8 - (int)$last->format('N')) . ' days');
                $prev = (clone $first)->modify('-1 month');
                $next = (clone $first)->modify('+1 month');
        }

        $items = EventRepository::calendarItems(team_filter_ids(), $from->format('Y-m-d 00:00:00'), $to->format('Y-m-d 00:00:00'), team_filter());
        $byDay = [];
        foreach ($items as $it) {
            $byDay[substr((string)$it['starts_at'], 0, 10)][] = $it;
        }

        $this->view('pages/calendar/index', [
            'pageTitle' => 'Calendari',
            'activeNav' => 'calendar',
            'calView'   => $view,
            'date'      => $date,
            'from'      => $from,
            'to'        => $to,
            'prev'      => $prev,
            'next'      => $next,
            'byDay'     => $byDay,
            'count'     => count($items),
            'teams'     => TeamScope::teams(),
            'team'      => team_filter(),
            'canManage' => Gate::allows('calendar.manage'),
        ]);
    }

    public function show(int $id): void
    {
        $event = $this->findOr404($id);
        $this->view('pages/calendar/show', [
            'pageTitle'  => $event['title'],
            'activeNav'  => 'calendar',
            'event'      => $event,
            'canManage'  => Gate::allows('calendar.manage'),
            'canAttend'  => Gate::allows('attendance.manage') && in_array($event['type'], EventRepository::TRAINING_TYPES, true),
            'attendance' => \App\Repositories\AttendanceRepository::summaryForEvent($id),
            'roster'     => $roster = \App\Repositories\AttendanceRepository::sheet($event),
            'away'       => \App\Repositories\AbsenceRepository::onDate(substr((string)$event['starts_at'], 0, 10), array_column($roster, 'id')),
            'showStatus' => Gate::allows('attendance.manage'),
            'removed'    => \App\Repositories\AttendanceRepository::removed($id),
            'pickable'   => Gate::allows('calendar.manage') ? EventRepository::selectablePlayers() : [],
            'sessions'   => class_exists(\App\Repositories\SessionRepository::class) ? \App\Repositories\SessionRepository::forEvent($id) : [],
        ]);
    }

    public function create(): void
    {
        $date = \DateTime::createFromFormat('!Y-m-d', (string)($_GET['data'] ?? '')) ?: new \DateTime('today');
        $this->form(null, [
            'date' => $date->format('Y-m-d'),
            'teams' => team_filter() ? [team_filter()] : [],
            'type' => isset(EventRepository::TYPES[$_GET['tipus'] ?? '']) ? (string)$_GET['tipus'] : 'entrenament',
        ]);
    }

    public function store(): void
    {
        [$d, $repeat, $participants, $teams, $errors] = EventRepository::validate($_POST, TeamScope::ids(), true);
        if ($errors) {
            $this->fail($errors, '/calendari/nou');
        }
        $id = EventRepository::create($d, $repeat, $participants, $teams);
        ActivityLog::add('event', 'Ha programat «' . $d['title'] . '»' . ($repeat ? ' (cada setmana)' : ''), $d['team_id'] ?: null);
        forget_input();
        flash('success', $repeat ? 'Esdeveniments creats cada setmana fins al ' . date('d/m/Y', strtotime($repeat)) . '.' : 'Esdeveniment creat.');
        redirect('/calendari/' . $id);
    }

    public function edit(int $id): void
    {
        $event = $this->findOr404($id);
        $this->form($event, [
            'teams'      => $event['team_id'] !== null ? [(int)$event['team_id']] : array_map('intval', array_column($event['teams'], 'id')),
            'who'        => $event['participants'] ? 'llista' : 'tots',
            'date'       => substr((string)$event['starts_at'], 0, 10),
            'start_time' => substr((string)$event['starts_at'], 11, 5),
            'end_time'   => $event['ends_at'] ? substr((string)$event['ends_at'], 11, 5) : '',
        ]);
    }

    public function update(int $id): void
    {
        $this->findOr404($id);
        [$d, , $participants, $teams, $errors] = EventRepository::validate($_POST, TeamScope::ids(), false);
        if ($errors) {
            $this->fail($errors, '/calendari/' . $id . '/editar');
        }
        EventRepository::update($id, $d, $participants, $teams);
        ActivityLog::add('event', 'Ha modificat «' . $d['title'] . '»', $d['team_id'] ?: null);
        forget_input();
        flash('success', 'Esdeveniment actualitzat.');
        redirect('/calendari/' . $id);
    }

    public function destroy(int $id): void
    {
        $event = $this->findOr404($id);
        $following = ($_POST['scope'] ?? '') === 'serie';
        $n = EventRepository::delete($event, $following);
        ActivityLog::add('event', 'Ha eliminat «' . $event['title'] . '» del ' . date('d/m', strtotime((string)$event['starts_at'])) . ($n > 1 ? ' i els següents de la sèrie' : ''), $event['team_id'] !== null ? (int)$event['team_id'] : null);
        flash('success', $n > 1 ? $n . ' esdeveniments eliminats.' : 'Esdeveniment eliminat.');
        redirect('/calendari?' . http_build_query(['data' => substr((string)$event['starts_at'], 0, 10)]));
    }

    /**
     * @param array<string,mixed>|null $event
     * @param array<string,string> $defaults
     */
    private function form(?array $event, array $defaults): void
    {
        $this->view('pages/calendar/form', [
            'pageTitle' => $event ? 'Edita l\'esdeveniment' : 'Nou esdeveniment',
            'activeNav' => 'calendar',
            'event'     => $event,
            'defaults'  => $defaults,
            'teams'     => TeamScope::teams(),
            'players'   => EventRepository::selectablePlayers(),
        ]);
        forget_input();
    }

    /**
     * @return array<string,mixed>
     */
    /** Afegeix o treu jugadores d'aquest esdeveniment concret (no de la sèrie ni de l'equip). */
    public function roster(int $id): void
    {
        $event = $this->findOr404($id);
        $action = (string)($_POST['action'] ?? '');
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['players'] ?? [])))));
        $allowed = array_map('intval', array_column(EventRepository::selectablePlayers(), 'id'));
        $ids = array_values(array_intersect($ids, $allowed));
        if (!$ids || !in_array($action, ['afegir', 'treure'], true)) {
            flash('error', 'Tria alguna jugadora.');
            redirect('/calendari/' . $id . '#convocades');
        }
        foreach ($ids as $pid) {
            \App\Repositories\AttendanceRepository::setRosterChange($id, $pid, $action === 'treure' ? 'treta' : 'afegida');
        }
        $n = count($ids);
        flash('success', $action === 'treure'
            ? ($n === 1 ? 'Jugadora treta d\'aquest entreno.' : $n . ' jugadores tretes d\'aquest entreno.')
            : ($n === 1 ? 'Jugadora afegida.' : $n . ' jugadores afegides.'));
        redirect('/calendari/' . $id . '#convocades');
    }

    private function findOr404(int $id): array
    {
        $event = EventRepository::find($id);
        if (!$event || !EventRepository::canAccess($event)) {
            throw new HttpException(404);
        }
        return $event;
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
