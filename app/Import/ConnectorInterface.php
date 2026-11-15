<?php
declare(strict_types=1);

namespace App\Import;

/**
 * Connector d'importació de dades esportives.
 * Cada proveïdor (federacions, plataformes, fitxers) és una classe independent
 * registrada a ConnectorRegistry. Un connector no ha de fer scraping ni accedir
 * a cap servei sense una API oficial o una autorització explícita.
 */
interface ConnectorInterface
{
    /** Identificador curt (s'usa a les URL). */
    public function key(): string;

    public function name(): string;

    public function description(): string;

    /**
     * Estat actual del connector.
     * @return array{state:string,note:string}  state: disponible | pendent | no_configurat
     */
    public function status(): array;

    /**
     * Quines dades pot importar (jugadors, partits, alineacions, gols, targetes, porters...).
     * @return array<int,string>
     */
    public function capabilities(): array;
}
