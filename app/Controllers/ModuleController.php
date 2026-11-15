<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Pàgines dels mòduls de les properes fases. Cada secció té la seva ruta,
 * permís i pàgina; quan es desenvolupi el mòdul, la ruta passarà al seu
 * controlador definitiu.
 */
final class ModuleController extends Controller
{
    public function stats(): void
    {
        $this->show('stats', 'Estadístiques', 'chart', 5, 'Rendiment individual i col·lectiu amb gràfics, només amb dades reals registrades.', [
            'Partits, gols, assistències de gol i targetes',
            'Assistència als entrenaments i evolució',
            'Per temporada i per equip',
            'Porters: gols encaixats, aturades, llançaments rebuts, % d\'aturades, penals i faltes directes',
        ]);
    }

    /**
     * @param array<int,string> $features
     */
    private function show(string $nav, string $title, string $icon, int $phase, string $intro, array $features): void
    {
        $this->view('pages/module', [
            'pageTitle'      => $title,
            'activeNav'      => $nav,
            'moduleIcon'     => $icon,
            'phase'          => $phase,
            'intro'          => $intro,
            'features'       => $features,
        ]);
    }
}
