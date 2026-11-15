<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Gate;
use App\Core\HttpException;
use App\Core\Migrator;
use App\Database\DemoSeeder;
use App\Repositories\ActivityLog;
use App\Repositories\Settings;

/**
 * Instal·lació inicial i actualització de l'esquema de la BD.
 *  - Sense usuaris: cal la install_key de config.php per crear l'administrador.
 *  - Amb usuaris: només un administrador pot aplicar migracions pendents.
 */
final class InstallController extends Controller
{
    public function show(): void
    {
        $state = $this->state();
        if ($state['mode'] === 'update') {
            $this->requireCoordinator();
        }
        $this->view('install/index', $state + ['pageTitle' => 'Instal·lació'], 'layouts/minimal');
        forget_input();
    }

    public function run(): void
    {
        $state = $this->state();

        if ($state['mode'] === 'update') {
            $this->requireCoordinator();
            $applied = Migrator::run();
            flash('success', $applied ? 'Base de dades actualitzada: ' . implode(', ', $applied) . '.' : 'La base de dades ja estava al dia.');
            redirect('/install');
        }

        if ($state['mode'] !== 'install') {
            redirect('/install');
        }

        $in = [
            'club_name' => trim((string)($_POST['club_name'] ?? '')),
            'name'      => trim((string)($_POST['name'] ?? '')),
            'email'     => mb_strtolower(trim((string)($_POST['email'] ?? ''))),
        ];
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password_confirmation'] ?? '');
        $key = (string)($_POST['install_key'] ?? '');
        $demo = !empty($_POST['demo']);

        $errors = [];
        $configuredKey = (string)Config::get('install_key', '');
        if ($configuredKey === '' || !hash_equals($configuredKey, $key)) {
            $errors[] = 'La clau d\'instal·lació no és correcta (és la "install_key" de config.php).';
        }
        if (mb_strlen($in['club_name']) < 2 || mb_strlen($in['club_name']) > 100) {
            $errors[] = 'Escriu el nom del club (2-100 caràcters).';
        }
        if (mb_strlen($in['name']) < 2 || mb_strlen($in['name']) > 120) {
            $errors[] = 'Escriu el teu nom (2-120 caràcters).';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correu electrònic no és vàlid.';
        }
        if (strlen($password) < 10) {
            $errors[] = 'La contrasenya ha de tenir com a mínim 10 caràcters.';
        } elseif ($password !== $password2) {
            $errors[] = 'Les contrasenyes no coincideixen.';
        }

        if ($errors) {
            remember_input($in);
            foreach ($errors as $err) {
                flash('error', $err);
            }
            redirect('/install');
        }

        Migrator::run();
        // Doble comprovació després de migrar (evita dues instal·lacions simultànies)
        if ((int)Database::value('SELECT COUNT(*) FROM users') > 0) {
            redirect('/login');
        }

        Database::query(
            'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)',
            [$in['name'], $in['email'], password_hash($password, PASSWORD_DEFAULT), Auth::ROLE_ADMIN, date('Y-m-d H:i:s')]
        );
        $userId = (int)Database::pdo()->lastInsertId();
        Settings::set('club_name', $in['club_name']);
        Settings::set('installed_at', date('Y-m-d H:i:s'));

        Auth::login($userId);
        ActivityLog::add('system', 'Ha instal·lat HockeyManager');

        if ($demo) {
            require_once HM_ROOT . '/database/seeds/DemoSeeder.php';
            $demoPassword = DemoSeeder::run($userId);
            flash('info', 'Dades de demostració carregades. Comptes de prova: entrenador1@demo.hockeymanager … entrenador5@demo.hockeymanager i jugador@demo.hockeymanager, contrasenya: ' . $demoPassword . ' (apunta-la, no es tornarà a mostrar).');
        }
        forget_input();
        flash('success', 'Instal·lació completada. Benvingut a HockeyManager!');
        redirect('/');
    }

    /**
     * @return array<string,mixed>
     */
    private function state(): array
    {
        if (!Config::isLoaded()) {
            return ['mode' => 'no-config'];
        }
        try {
            Database::pdo();
        } catch (\Throwable $e) {
            error_log('[HockeyManager] install db: ' . $e->getMessage());
            return ['mode' => 'db-error'];
        }
        $hasUsers = Database::tableExists('users') && (int)Database::value('SELECT COUNT(*) FROM users') > 0;
        if (!$hasUsers) {
            return ['mode' => 'install', 'keyConfigured' => (string)Config::get('install_key', '') !== ''];
        }
        return ['mode' => 'update', 'pending' => Migrator::pending()];
    }

    private function requireCoordinator(): void
    {
        if (!Auth::check()) {
            $_SESSION['hm_intended'] = '/install';
            redirect('/login');
        }
        if (!Gate::allows('system.install')) {
            throw new HttpException(403);
        }
    }
}
