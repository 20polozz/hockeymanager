<?php
declare(strict_types=1);

namespace App\Import\Connectors;

final class HockeyTelemetryConnector extends ExternalSourceConnector
{
    public function key(): string { return 'hockey-telemetry'; }
    public function name(): string { return 'Hockey Telemetry'; }
    public function description(): string { return 'Plataforma d\'estadístiques avançades d\'hoquei patins.'; }
}
