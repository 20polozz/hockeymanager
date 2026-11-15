<?php
/**
 * HockeyManager — configuració.
 *
 * 1. Copia aquest fitxer com a "config.php" a la mateixa carpeta.
 * 2. Omple les dades de la base de dades PRÒPIA de HockeyManager
 *    (no la de HockeyFantasy: són independents perquè l'app es pugui
 *    moure a un altre hosting/domini sense tocar res més).
 * 3. Posa una "install_key" llarga i obre /hockeymanager/install.
 *
 * config.php NO es puja a GitHub (.gitignore): s'ha de pujar a mà per FTP.
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
        // null = es detecta sol (/hockeymanager ara, "" en un domini propi)
        'base_path' => null,
    ],

    // Clau necessària per fer la PRIMERA instal·lació (crear el coordinador).
    // Un cop hi ha usuaris, l'instal·lador només el pot fer servir un coordinador.
    'install_key' => '',
];
