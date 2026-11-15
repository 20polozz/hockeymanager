<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Gate;
use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\EventRepository;
use App\Repositories\ExerciseRepository;
use App\Repositories\SessionRepository;

/**
 * Entrenaments: sessions planificades amb exercicis de la biblioteca.
 */
final class SessionController extends Controller
{
    public function index(): void
    {
        $this->view('pages/sessions/index', [
            'pageTitle' => 'Entrenaments',
            'activeNav' => 'trainings',
            'teams'     => TeamScope::teams(),
            'team'      => team_filter(),
            'sessions'  => SessionRepository::visible(team_filter()),
            'canManage' => Gate::allows('trainings.manage'),
        ]);
    }

    public function create(): void
    {
        $defaults = ['team_id' => (string)team_filter()];
        $eventId = (int)($_GET['esdeveniment'] ?? 0);
        if ($eventId > 0) {
            $ev = EventRepository::find($eventId);
            if ($ev && $ev['type'] === 'entrenament' && EventRepository::canAccess($ev)) {
                $defaults = [
                    'event_id' => (string)$eventId,
                    'team_id'  => (string)($ev['team_id'] ?? 0),
                    'title'    => 'Sessió ' . $ev['team_name'] . ' · ' . date('d/m', strtotime((string)$ev['starts_at'])),
                ];
            }
        }
        $this->form(null, $defaults);
    }

    public function store(): void
    {
        [$d, $errors] = SessionRepository::validate($_POST);
        if ($errors) {
            $this->fail($errors, '/entrenaments/nou');
        }
        $id = SessionRepository::create($d);
        ActivityLog::add('session', 'Ha creat la sessió «' . $d['title'] . '»', $d['team_id'] ?: null);
        forget_input();
        flash('success', 'Sessió creada. Ara afegeix-hi exercicis.');
        redirect('/entrenaments/' . $id);
    }

    public function show(int $id): void
    {
        $s = $this->findOr404($id);
        $this->view('pages/sessions/show', [
            'pageTitle' => $s['title'],
            'activeNav' => 'trainings',
            's'         => $s,
            'library'   => Gate::allows('trainings.manage') ? ExerciseRepository::all() : [],
            'canManage' => Gate::allows('trainings.manage'),
        ]);
    }

    public function edit(int $id): void
    {
        $this->form($this->findOr404($id), []);
    }

    public function update(int $id): void
    {
        $this->findOr404($id);
        [$d, $errors] = SessionRepository::validate($_POST);
        if ($errors) {
            $this->fail($errors, '/entrenaments/' . $id . '/editar');
        }
        SessionRepository::update($id, $d);
        forget_input();
        flash('success', 'Sessió actualitzada.');
        redirect('/entrenaments/' . $id);
    }

    public function destroy(int $id): void
    {
        $s = $this->findOr404($id);
        SessionRepository::delete($id);
        flash('success', 'Sessió «' . $s['title'] . '» eliminada.');
        redirect('/entrenaments');
    }

    /** Afegir, moure, editar o treure exercicis de la sessió. */
    public function items(int $id): void
    {
        $this->findOr404($id);
        $action = (string)($_POST['action'] ?? '');
        $item = (int)($_POST['item'] ?? 0);
        switch ($action) {
            case 'add':
                $exId = (int)($_POST['exercise_id'] ?? 0);
                if (!ExerciseRepository::find($exId)) {
                    flash('error', 'Tria un exercici de la llista.');
                    break;
                }
                SessionRepository::addExercise($id, $exId);
                flash('success', 'Exercici afegit.');
                break;
            case 'up':
            case 'down':
                SessionRepository::move($id, $item, $action === 'up' ? -1 : 1);
                break;
            case 'save':
                $raw = trim((string)($_POST['duration_min'] ?? ''));
                $min = ctype_digit($raw) && (int)$raw >= 1 && (int)$raw <= 240 ? (int)$raw : null;
                SessionRepository::updateItem($id, $item, $min, mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 255));
                flash('success', 'Canvis desats.');
                break;
            case 'remove':
                SessionRepository::removeItem($id, $item);
                flash('success', 'Exercici tret de la sessió.');
                break;
        }
        redirect('/entrenaments/' . $id . ($item > 0 ? '#item-' . $item : ''));
    }

    /**
     * @param array<string,mixed>|null $s
     * @param array<string,string> $defaults
     */
    private function form(?array $s, array $defaults): void
    {
        $this->view('pages/sessions/form', [
            'pageTitle' => $s ? 'Edita la sessió' : 'Nova sessió',
            'activeNav' => 'trainings',
            's'         => $s,
            'defaults'  => $defaults,
            'teams'     => TeamScope::teams(),
            'events'    => SessionRepository::linkableEvents(),
        ]);
        forget_input();
    }

    /**
     * @return array<string,mixed>
     */
    private function findOr404(int $id): array
    {
        $s = SessionRepository::find($id);
        if (!$s || !SessionRepository::canAccess($s)) {
            throw new HttpException(404);
        }
        return $s;
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
