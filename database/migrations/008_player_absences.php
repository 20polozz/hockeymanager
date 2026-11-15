<?php
/**
 * Migració 008 — absències i baixes temporals de jugadores
 * (lesió, malaltia, viatge…): un període en què la jugadora no pot venir.
 * ends_on NULL = sense data de tornada.
 */
return [
    "CREATE TABLE IF NOT EXISTS player_absences (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        player_id INT UNSIGNED NOT NULL,
        type VARCHAR(20) NOT NULL,
        starts_on DATE NOT NULL,
        ends_on DATE NULL,
        note VARCHAR(255) NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        KEY idx_pabs_player (player_id, starts_on),
        KEY idx_pabs_dates (starts_on, ends_on),
        CONSTRAINT fk_pabs_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
