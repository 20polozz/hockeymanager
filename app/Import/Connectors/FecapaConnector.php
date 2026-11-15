<?php
declare(strict_types=1);

namespace App\Import\Connectors;

final class FecapaConnector extends ExternalSourceConnector
{
    public function key(): string { return 'fecapa'; }
    public function name(): string { return 'FECAPA'; }
    public function description(): string { return 'Federació Catalana de Patinatge: resultats i actes de les competicions catalanes.'; }
}
