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
    public function attendance(): void
    {
        $this->show('attendance', 'Assistència', 'checklist', 2, 'Passa llista des del mòbil en pocs tocs i segueix l\'evolució de cada jugador.', [
            'Seleccionar equip i entrenament',
            'Marcar presents, absents, absències justificades i retards',
            'Observacions per jugador',
            'Percentatge per jugador i per equip',
            'Historial mensual i jugadors/es amb més absències',
        ]);
    }

    public function calendar(): void
    {
        $this->show('calendar', 'Calendari', 'calendar', 2, 'Tots els entrenaments, partits i esdeveniments del club en un sol lloc.', [
            'Vistes mensual, setmanal i diària',
            'Colors diferents per a entrenaments, partits i esdeveniments especials',
            'Crear, modificar i eliminar esdeveniments',
            'Filtrar per equip',
        ]);
    }

    public function exercises(): void
    {
        $this->show('exercises', 'Exercicis', 'cone', 3, 'Biblioteca d\'exercicis d\'hoquei patins per construir sessions.', [
            'Fitxa: objectiu, categoria, dificultat, durada, material, edat i imatge/vídeo',
            'Categories: patinatge, control de bola, passades, xuts, defensa, tàctica, preparació física i porters',
            'Cerca, filtres i preferits',
        ]);
    }

    public function trainings(): void
    {
        $this->show('trainings', 'Entrenaments', 'dumbbell', 3, 'Planifica les sessions combinant exercicis de la biblioteca.', [
            'Crear sessions amb diversos exercicis ordenats',
            'Durada total calculada automàticament',
            'Vincular la sessió a un entrenament del calendari',
        ]);
    }

    public function matches(): void
    {
        $this->show('matches', 'Partits', 'trophy', 4, 'Calendari de competició, resultats i historial per temporada.', [
            'Rival, competició, data, hora, pista i local/visitant',
            'Resultat i estat del partit',
            'Partits futurs, disputats i historial per temporada',
        ]);
    }

    public function callups(): void
    {
        $this->show('callups', 'Convocatòries', 'clipboard', 4, 'L\'entrenador convoca i els jugadors/es confirmen des del mòbil.', [
            'Seleccionar partit i jugadors/es convocats',
            'Confirmar o rebutjar la convocatòria (jugador)',
            'Seguiment de confirmats, rebutjats i pendents',
            'Separació entre la resposta a la convocatòria i l\'assistència real al partit',
        ]);
    }

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
            'showTeamPicker' => true,
            'moduleIcon'     => $icon,
            'phase'          => $phase,
            'intro'          => $intro,
            'features'       => $features,
        ]);
    }
}
