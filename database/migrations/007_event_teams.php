<?php
/**
 * Migració 007 — esdeveniments compartits per diversos equips
 * (p. ex. l'entrenament de pista de tots els equips femenins).
 * Si un esdeveniment té files a event_teams, és per a aquests equips.
 */
return [
    "CREATE TABLE IF NOT EXISTS event_teams (
        event_id INT UNSIGNED NOT NULL,
        team_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (event_id, team_id),
        KEY idx_et_team (team_id),
        CONSTRAINT fk_et_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        CONSTRAINT fk_et_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
