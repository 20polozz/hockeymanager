<?php
declare(strict_types=1);

namespace App\Import\Connectors;

use App\Import\ConnectorInterface;

/**
 * Base per a fonts externes encara no integrades. No fa cap petició de xarxa:
 * queda "pendent" fins que hi hagi una API oficial o un acord d'accés, i
 * aleshores s'implementarà el mètode d'importació corresponent.
 */
abstract class ExternalSourceConnector implements ConnectorInterface
{
    public function status(): array
    {
        return [
            'state' => 'pendent',
            'note'  => 'Pendent d\'investigar. No s\'ha trobat cap API pública documentada; no es farà scraping. '
                     . 'Caldrà una API oficial o l\'autorització del proveïdor per activar-lo.',
        ];
    }

    public function capabilities(): array
    {
        return ['Partits jugats', 'Gols', 'Assistències', 'Targetes', 'Alineacions', 'Estadístiques de porteria'];
    }
}
