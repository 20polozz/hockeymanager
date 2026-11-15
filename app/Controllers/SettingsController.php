<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\ActivityLog;

final class SettingsController extends Controller
{
    public function index(): void
    {
        $this->view('pages/settings', [
            'pageTitle' => 'Configuració',
            'activeNav' => 'settings',
            'user'      => Auth::user(),
        ]);
    }

    public function updatePassword(): void
    {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirmation'] ?? '');

        $hash = (string)Database::value('SELECT password_hash FROM users WHERE id = ?', [Auth::id()]);
        if (!password_verify($current, $hash)) {
            flash('error', 'La contrasenya actual no és correcta.');
        } elseif (strlen($new) < \App\Repositories\UserRepository::MIN_PASSWORD) {
            flash('error', 'La nova contrasenya ha de tenir com a mínim ' . \App\Repositories\UserRepository::MIN_PASSWORD . ' caràcters.');
        } elseif ($new !== $confirm) {
            flash('error', 'Les contrasenyes noves no coincideixen.');
        } else {
            Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), Auth::id()]);
            session_regenerate_id(true);
            ActivityLog::add('login', 'Ha canviat la contrasenya');
            flash('success', 'Contrasenya actualitzada.');
        }
        redirect('/configuracio');
    }
}
