<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\TeamScope;
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
}
