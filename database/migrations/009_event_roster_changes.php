<?php
/**
 * Migració 009 — canvis puntuals a la llista d'un esdeveniment:
 * afegir una jugadora a un entreno concret o treure-la'n, sense tocar l'equip ni la sèrie.
 */
return [
    "CREATE TABLE IF NOT EXISTS event_roster_changes (
        event_id INT UNSIGNED NOT NULL,
        player_id INT UNSIGNED NOT NULL,
        `change` ENUM('afegida','treta') NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (event_id, player_id),
        KEY idx_erc_player (player_id),
        CONSTRAINT fk_erc_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        CONSTRAINT fk_erc_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
