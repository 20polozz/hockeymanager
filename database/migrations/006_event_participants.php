<?php
/**
 * Migració 006 — esdeveniments per a tot el club o per a una llista de
 * jugadors/es (p. ex. grups de gimnàs), tipus "gimnàs" i sèries setmanals.
 *  - events.team_id NULL = tot el club
 *  - event_players: si un esdeveniment en té, la llista de qui hi ha d'anar és aquesta
 *  - events.series_id agrupa les repeticions setmanals
 */
return [
    "ALTER TABLE events
        MODIFY team_id INT UNSIGNED NULL,
        MODIFY type ENUM('entrenament','gimnas','especial') NOT NULL DEFAULT 'entrenament',
        ADD COLUMN IF NOT EXISTS series_id CHAR(16) NULL AFTER notes,
        ADD INDEX IF NOT EXISTS idx_event_series (series_id)",

    "CREATE TABLE IF NOT EXISTS event_players (
        event_id INT UNSIGNED NOT NULL,
        player_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (event_id, player_id),
        KEY idx_ep_player (player_id),
        CONSTRAINT fk_ep_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        CONSTRAINT fk_ep_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
