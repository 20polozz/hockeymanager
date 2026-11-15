<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\TeamScope;
use App\Repositories\ActivityLog;
use App\Repositories\UserRepository;

/**
 * Gestió d'usuaris (administradors i coordinadors).
 * Només un administrador pot crear o modificar comptes d'administrador.
 */
final class UserController extends Controller
{
    public function index(): void
    {
        $role = (string)($_GET['rol'] ?? '');
        $this->view('pages/users/index', [
            'pageTitle' => 'Usuaris',
            'activeNav' => 'users',
            'role'      => isset(Auth::roleLabels()[$role]) ? $role : '',
            'users'     => UserRepository::all($role),
        ]);
    }

    public function create(): void
    {
        $this->form(null, ['role' => (string)($_GET['rol'] ?? 'entrenador')]);
    }

    public function store(): void
    {
        [$d, $password, $staff, $errors] = UserRepository::validate($_POST, null, TeamScope::ids(), $this->allowedRoles());
        if ($errors) {
            $this->fail($errors, '/usuaris/nou');
        }
        $id = UserRepository::create($d, (string)$password, $staff);
        ActivityLog::add('system', 'Ha creat el compte de ' . $d['name'] . ' (' . Auth::roleLabels()[$d['role']] . ')');
        forget_input();
        flash('success', 'Compte creat. Usuari: ' . $d['username'] . '. Comunica-li la contrasenya i recomana-li que la canviï a Configuració.');
        redirect('/usuaris/' . $id . '/editar');
    }

    public function edit(int $id): void
    {
        $this->form($this->editable($id), []);
    }

    public function update(int $id): void
    {
        $user = $this->editable($id);
        [$d, $password, $staff, $errors] = UserRepository::validate($_POST, $id, TeamScope::ids(), $this->allowedRoles());
        if ($id === Auth::id() && $d['role'] !== $user['role']) {
            $errors[] = 'No pots canviar el teu propi rol.';
        }
        if ($errors) {
            $this->fail($errors, '/usuaris/' . $id . '/editar');
        }
        UserRepository::update($id, $d, $password, $staff);
        forget_input();
        flash('success', 'Compte actualitzat' . ($password !== null ? ' (amb contrasenya nova)' : '') . '.');
        redirect('/usuaris');
    }

    public function toggle(int $id): void
    {
        $user = $this->editable($id);
        if ($id === Auth::id()) {
            flash('error', 'No pots desactivar el teu propi compte.');
            redirect('/usuaris');
        }
        $active = (int)$user['active'] !== 1;
        UserRepository::setActive($id, $active);
        flash('success', $user['name'] . ($active ? ' torna a tenir accés.' : ' ja no pot entrar a l\'aplicació.'));
        redirect('/usuaris');
    }

    /**
     * @return array<int,string>
     */
    private function allowedRoles(): array
    {
        $roles = ['coordinador', 'entrenador', 'jugador'];
        if (Auth::hasRole(Auth::ROLE_ADMIN)) {
            array_unshift($roles, 'admin');
        }
        return $roles;
    }

    /**
     * @return array<string,mixed>
     */
    private function editable(int $id): array
    {
        $u = UserRepository::find($id);
        if (!$u) {
            throw new HttpException(404);
        }
        if ($u['role'] === 'admin' && !Auth::hasRole(Auth::ROLE_ADMIN)) {
            throw new HttpException(403, 'Només un administrador pot modificar un compte d\'administrador.');
        }
        return $u;
    }

    /**
     * @param array<string,mixed>|null $user
     * @param array<string,string> $defaults
     */
    private function form(?array $user, array $defaults): void
    {
        $this->view('pages/users/form', [
            'pageTitle' => $user ? 'Edita l\'usuari' : 'Nou usuari',
            'activeNav' => 'users',
            'user'      => $user,
            'defaults'  => $defaults,
            'roles'     => $this->allowedRoles(),
            'teams'     => TeamScope::teams(),
            'players'   => UserRepository::linkablePlayers($user ? (int)$user['player_id'] : null),
        ]);
        forget_input();
    }

    /**
     * @param array<int,string> $errors
     */
    private function fail(array $errors, string $back): void
    {
        $in = $_POST;
        unset($in['password']);
        remember_input($in);
        foreach ($errors as $err) {
            flash('error', $err);
        }
        redirect($back);
    }
}
