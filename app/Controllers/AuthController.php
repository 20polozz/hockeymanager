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
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            remember_input(['email' => $email]);
            flash('error', 'Escriu un correu vàlid i la contrasenya.');
            redirect('/login');
        }

        $error = Auth::attempt($email, $password);
        if ($error !== null) {
            remember_input(['email' => $email]);
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
