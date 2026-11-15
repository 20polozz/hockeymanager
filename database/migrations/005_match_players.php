<?php
/**
 * Migració 005 — acta del partit: participació i estadístiques per jugador/a.
 * És l'assistència REAL al partit (diferent de la resposta a la convocatòria).
 * Els camps de porter/a queden a NULL si no s'han anotat (no s'inventen).
 */
return [
    "CREATE TABLE IF NOT EXISTS match_players (
        match_id INT UNSIGNED NOT NULL,
        player_id INT UNSIGNED NOT NULL,
        played TINYINT(1) NOT NULL DEFAULT 1,
        goals TINYINT UNSIGNED NOT NULL DEFAULT 0,
        assists TINYINT UNSIGNED NOT NULL DEFAULT 0,
        blue_cards TINYINT UNSIGNED NOT NULL DEFAULT 0,
        red_cards TINYINT UNSIGNED NOT NULL DEFAULT 0,
        gk_goals_against TINYINT UNSIGNED NULL,
        gk_saves SMALLINT UNSIGNED NULL,
        gk_shots SMALLINT UNSIGNED NULL,
        gk_penalties_faced TINYINT UNSIGNED NULL,
        gk_penalties_saved TINYINT UNSIGNED NULL,
        gk_free_hits_faced TINYINT UNSIGNED NULL,
        gk_free_hits_saved TINYINT UNSIGNED NULL,
        PRIMARY KEY (match_id, player_id),
        KEY idx_mp_player (player_id),
        CONSTRAINT fk_mp_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
        CONSTRAINT fk_mp_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
