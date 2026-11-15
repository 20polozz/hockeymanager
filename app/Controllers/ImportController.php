<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Import\Connectors\CsvPlayersConnector;
use App\Import\ConnectorRegistry;
use App\Repositories\ActivityLog;

final class ImportController extends Controller
{
    public function index(): void
    {
        $this->view('pages/import/index', [
            'pageTitle'  => 'Importació de dades',
            'activeNav'  => 'settings',
            'connectors' => ConnectorRegistry::all(),
        ]);
    }

    /** Puja el CSV i mostra una previsualització (encara no desa res). */
    public function preview(): void
    {
        $f = $_FILES['file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$f['tmp_name'])) {
            flash('error', 'Tria un fitxer CSV.');
            redirect('/configuracio/importacio');
        }
        if ((int)$f['size'] > CsvPlayersConnector::MAX_BYTES) {
            flash('error', 'El fitxer és massa gran (màxim 1 MB).');
            redirect('/configuracio/importacio');
        }
        $rows = (new CsvPlayersConnector())->parse((string)file_get_contents((string)$f['tmp_name']));
        if (!$rows) {
            flash('error', 'El fitxer és buit o no té el format esperat.');
            redirect('/configuracio/importacio');
        }
        $_SESSION['hm_import'] = $rows;
        $this->view('pages/import/preview', [
            'pageTitle' => 'Revisa la importació',
            'activeNav' => 'settings',
            'rows'      => $rows,
        ]);
    }

    public function confirm(): void
    {
        $rows = $_SESSION['hm_import'] ?? null;
        unset($_SESSION['hm_import']);
        if (!is_array($rows) || !$rows) {
            flash('error', 'No hi ha cap importació pendent. Torna a pujar el fitxer.');
            redirect('/configuracio/importacio');
        }
        $r = (new CsvPlayersConnector())->import($rows);
        ActivityLog::add('player', 'Ha importat jugadors/es des d\'un CSV (' . $r['created'] . ' noves)');
        flash('success', 'Importació feta: ' . $r['created'] . ' fitxes noves, ' . $r['added'] . ' afegides a un altre equip, ' . $r['skipped'] . ' files omeses.');
        redirect('/jugadors');
    }

    /** Plantilla CSV d'exemple. */
    public function template(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla-jugadors.csv"');
        echo "\xEF\xBB\xBF" . "nom;cognoms;data_naixement;posicio;equip;dorsal\r\n"
            . "Laia;Exemple Exemple;15/03/2010;jugador;Fem 17;7\r\n"
            . "Marta;Exemple Prova;02/11/2009;porter;Fem 17;1\r\n";
        exit;
    }
}
