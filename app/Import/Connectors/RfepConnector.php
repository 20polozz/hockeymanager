<?php
declare(strict_types=1);

namespace App\Import\Connectors;

final class RfepConnector extends ExternalSourceConnector
{
    public function key(): string { return 'rfep'; }
    public function name(): string { return 'RFEP'; }
    public function description(): string { return 'Real Federación Española de Patinaje: competicions estatals.'; }
}
