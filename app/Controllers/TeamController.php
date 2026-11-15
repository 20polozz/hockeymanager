<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\TeamRepository;

final class TeamController extends Controller
{
    public function index(): void
    {
        $this->view('pages/teams/index', [
            'pageTitle' => 'Equips',
            'activeNav' => 'teams',
            'teams'     => TeamRepository::overview(TeamScope::ids()),
        ]);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        [$data, $errors] = TeamRepository::validate($_POST);
        if ($errors) {
            $this->fail($data, $errors, '/equips/nou');
        }
        $id = TeamRepository::create($data['name'], $data['category_id'] ?: null, $data['color']);
        ActivityLog::add('team', 'Ha creat l\'equip ' . $data['name'], $id);
        forget_input();
        flash('success', 'Equip «' . $data['name'] . '» creat.');
        redirect('/equips');
    }

    public function edit(int $id): void
    {
        $this->form($this->findOr404($id));
    }

    public function update(int $id): void
    {
        $this->findOr404($id);
        [$data, $errors] = TeamRepository::validate($_POST);
        if ($errors) {
            $this->fail($data, $errors, '/equips/' . $id . '/editar');
        }
        TeamRepository::update($id, $data['name'], $data['category_id'] ?: null, $data['color']);
        ActivityLog::add('team', 'Ha editat l\'equip ' . $data['name'], $id);
        forget_input();
        flash('success', 'Equip actualitzat.');
        redirect('/equips');
    }

    public function destroy(int $id): void
    {
        $team = $this->findOr404($id);
        $blocker = TeamRepository::deleteBlocker($id);
        if ($blocker !== null) {
            flash('error', $blocker);
            redirect('/equips/' . $id . '/editar');
        }
        TeamRepository::delete($id);
        ActivityLog::add('team', 'Ha eliminat l\'equip ' . $team['name']);
        flash('success', 'Equip «' . $team['name'] . '» eliminat.');
        redirect('/equips');
    }

    /**
     * @param array<string,mixed>|null $team
     */
    private function form(?array $team): void
    {
        $this->view('pages/teams/form', [
            'pageTitle'  => $team ? 'Edita l\'equip' : 'Nou equip',
            'activeNav'  => 'teams',
            'team'       => $team,
            'categories' => TeamRepository::categories(),
        ]);
        forget_input();
    }

    /**
     * @return array<string,mixed>
     */
    private function findOr404(int $id): array
    {
        $team = TeamRepository::find($id);
        if (!$team || !TeamScope::canAccess($id)) {
            throw new HttpException(404);
        }
        return $team;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<int,string> $errors
     */
    private function fail(array $data, array $errors, string $back): void
    {
        remember_input(array_map('strval', $data));
        foreach ($errors as $err) {
            flash('error', $err);
        }
        redirect($back);
    }
}
