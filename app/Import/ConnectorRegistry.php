<?php
declare(strict_types=1);

namespace App\Import;

use App\Import\Connectors\CsvPlayersConnector;
use App\Import\Connectors\FecapaConnector;
use App\Import\Connectors\HockeyTelemetryConnector;
use App\Import\Connectors\RfepConnector;

/**
 * Llista de connectors disponibles. Per afegir-ne un de nou: crea una classe
 * que implementi ConnectorInterface i afegeix-la aquí.
 */
final class ConnectorRegistry
{
    /**
     * @return array<string,ConnectorInterface>
     */
    public static function all(): array
    {
        $list = [new CsvPlayersConnector(), new FecapaConnector(), new RfepConnector(), new HockeyTelemetryConnector()];
        $out = [];
        foreach ($list as $c) {
            $out[$c->key()] = $c;
        }
        return $out;
    }

    public static function get(string $key): ?ConnectorInterface
    {
        return self::all()[$key] ?? null;
    }
}
