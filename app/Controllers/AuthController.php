<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\ActivityLog;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (!Database::tableExists('users')) {
            redirect('/install');
        }
        $this->view('auth/login', ['pageTitle' => 'Iniciar sessió'], 'layouts/minimal');
        forget_input();
    }

    public function login(): void
    {
        $login = trim((string)($_POST['login'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($login === '' || $password === '' || mb_strlen($login) > 190) {
            remember_input(['login' => $login]);
            flash('error', 'Escriu l\'usuari (o el correu) i la contrasenya.');
            redirect('/login');
        }

        $error = Auth::attempt($login, $password);
        if ($error !== null) {
            remember_input(['login' => $login]);
            flash('error', $error);
            redirect('/login');
        }

        ActivityLog::add('login', 'Ha iniciat sessió');
        $to = (string)($_SESSION['hm_intended'] ?? '/');
        unset($_SESSION['hm_intended']);
        redirect(strpos($to, '/') === 0 ? $to : '/');
    }

    public function logout(): void
    {
        Auth::logout();
        session_start();
        flash('success', 'Has tancat la sessió.');
        redirect('/login');
    }
}
