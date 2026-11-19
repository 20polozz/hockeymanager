<?php
/**
 * HockeyManager — configuració.
 *
 * 1. Copia aquest fitxer com a "config.php" a la mateixa carpeta.
 * 2. Omple les dades de la base de dades de HockeyManager.
 * 3. Posa una "install_key" llarga (només cal per a la primera instal·lació, /install).
 *
 * config.php NO es puja a GitHub (.gitignore): s'ha de crear a mà al servidor.
 */
return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'hockeymanager',
        'user'    => '',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'app' => [
        'name'      => 'HockeyManager',
        'club_name' => 'Club Hoquei Patins',
        // 'production' amaga els errors; 'development' els mostra
        'env'       => 'production',
        'timezone'  => 'Europe/Madrid',
        // null = es detecta sol ("" a l'arrel del domini, "/carpeta" dins d'una carpeta)
        'base_path' => null,
    ],

    // Clau necessària per fer la PRIMERA instal·lació (crear el coordinador).
    // Un cop hi ha usuaris, l'instal·lador només el pot fer servir un coordinador.
    'install_key' => '',
];
