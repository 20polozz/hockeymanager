<?php
/**
 * Migració 004 — biblioteca d'exercicis i sessions d'entrenament.
 */
$t = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return [
    "CREATE TABLE IF NOT EXISTS exercises (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        category ENUM('patinatge','control','passades','xuts','defensa','tactica','fisica','porters') NOT NULL,
        description TEXT NULL,
        objective VARCHAR(255) NULL,
        difficulty TINYINT UNSIGNED NOT NULL DEFAULT 2,
        duration_min SMALLINT UNSIGNED NULL,
        material VARCHAR(255) NULL,
        media_url VARCHAR(500) NULL,
        recommended_age VARCHAR(60) NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        KEY idx_ex_category (category),
        CONSTRAINT fk_ex_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE IF NOT EXISTS exercise_favorites (
        user_id INT UNSIGNED NOT NULL,
        exercise_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (user_id, exercise_id),
        CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_fav_ex FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
    ) $t",

    "CREATE TABLE IF NOT EXISTS training_sessions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(120) NOT NULL,
        team_id INT UNSIGNED NULL,
        event_id INT UNSIGNED NULL,
        notes TEXT NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        KEY idx_ts_event (event_id),
        CONSTRAINT fk_tsess_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
        CONSTRAINT fk_tsess_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
        CONSTRAINT fk_tsess_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE IF NOT EXISTS session_exercises (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id INT UNSIGNED NOT NULL,
        exercise_id INT UNSIGNED NOT NULL,
        position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        duration_min SMALLINT UNSIGNED NULL,
        notes VARCHAR(255) NULL,
        KEY idx_se_session (session_id, position),
        CONSTRAINT fk_se_session FOREIGN KEY (session_id) REFERENCES training_sessions(id) ON DELETE CASCADE,
        CONSTRAINT fk_se_ex FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
    ) $t",
];
