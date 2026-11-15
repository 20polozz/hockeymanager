<?php
/**
 * Migració 001 — taules principals del club.
 * Cada migració retorna una llista de sentències SQL (MariaDB / MySQL).
 *
 * Decisions de disseny:
 *  - players és el perfil esportiu permanent; team_players guarda a quin
 *    equip ha estat cada temporada (historial), amb left_at quan en surt.
 *  - callup_players.response és la RESPOSTA a la convocatòria; l'assistència
 *    real al partit es guardarà a part (match_players, fase de partits).
 */
$t = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return [
    "CREATE TABLE settings (
        `key` VARCHAR(64) NOT NULL PRIMARY KEY,
        `value` TEXT NOT NULL
    ) $t",

    "CREATE TABLE seasons (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL,
        starts_on DATE NOT NULL,
        ends_on DATE NOT NULL,
        is_current TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY uq_season_name (name)
    ) $t",

    "CREATE TABLE categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(40) NOT NULL,
        min_age TINYINT UNSIGNED NULL,
        max_age TINYINT UNSIGNED NULL,
        sort_order SMALLINT NOT NULL DEFAULT 0,
        UNIQUE KEY uq_category_name (name)
    ) $t",

    "CREATE TABLE teams (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        season_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED NULL,
        name VARCHAR(80) NOT NULL,
        color CHAR(7) NOT NULL DEFAULT '#1e3a8a',
        created_at DATETIME NOT NULL,
        KEY idx_team_season (season_id),
        CONSTRAINT fk_team_season FOREIGN KEY (season_id) REFERENCES seasons(id),
        CONSTRAINT fk_team_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE players (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(60) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        birth_date DATE NULL,
        position ENUM('jugador','porter') NOT NULL DEFAULT 'jugador',
        photo_path VARCHAR(255) NULL,
        notes TEXT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        KEY idx_player_name (last_name, first_name)
    ) $t",

    "CREATE TABLE team_players (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        team_id INT UNSIGNED NOT NULL,
        player_id INT UNSIGNED NOT NULL,
        dorsal TINYINT UNSIGNED NULL,
        joined_on DATE NOT NULL,
        left_at DATE NULL,
        KEY idx_tp_team (team_id, left_at),
        KEY idx_tp_player (player_id),
        CONSTRAINT fk_tp_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        CONSTRAINT fk_tp_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    ) $t",

    "CREATE TABLE users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('coordinador','entrenador','jugador') NOT NULL,
        player_id INT UNSIGNED NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_user_email (email),
        CONSTRAINT fk_user_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE team_staff (
        team_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        staff_role ENUM('principal','ajudant') NOT NULL DEFAULT 'principal',
        PRIMARY KEY (team_id, user_id),
        CONSTRAINT fk_ts_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        CONSTRAINT fk_ts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) $t",

    "CREATE TABLE events (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        team_id INT UNSIGNED NOT NULL,
        type ENUM('entrenament','especial') NOT NULL DEFAULT 'entrenament',
        title VARCHAR(120) NOT NULL,
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NULL,
        location VARCHAR(150) NULL,
        notes TEXT NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        KEY idx_event_team_date (team_id, starts_at),
        CONSTRAINT fk_event_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        CONSTRAINT fk_event_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE matches (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        team_id INT UNSIGNED NOT NULL,
        season_id INT UNSIGNED NOT NULL,
        opponent VARCHAR(120) NOT NULL,
        competition VARCHAR(120) NULL,
        starts_at DATETIME NOT NULL,
        location VARCHAR(150) NULL,
        is_home TINYINT(1) NOT NULL DEFAULT 1,
        goals_for TINYINT UNSIGNED NULL,
        goals_against TINYINT UNSIGNED NULL,
        status ENUM('programat','jugat','ajornat','cancel·lat') NOT NULL DEFAULT 'programat',
        created_by INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        KEY idx_match_team_date (team_id, starts_at),
        CONSTRAINT fk_match_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        CONSTRAINT fk_match_season FOREIGN KEY (season_id) REFERENCES seasons(id),
        CONSTRAINT fk_match_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE attendance (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_id INT UNSIGNED NOT NULL,
        player_id INT UNSIGNED NOT NULL,
        status ENUM('present','absent','justificat','retard') NOT NULL,
        observation VARCHAR(255) NULL,
        recorded_by INT UNSIGNED NULL,
        recorded_at DATETIME NOT NULL,
        UNIQUE KEY uq_attendance (event_id, player_id),
        KEY idx_att_player (player_id),
        CONSTRAINT fk_att_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        CONSTRAINT fk_att_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
        CONSTRAINT fk_att_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE callups (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        match_id INT UNSIGNED NOT NULL,
        message TEXT NULL,
        meeting_at DATETIME NULL,
        sent_at DATETIME NULL,
        created_by INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uq_callup_match (match_id),
        CONSTRAINT fk_callup_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
        CONSTRAINT fk_callup_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) $t",

    "CREATE TABLE callup_players (
        callup_id INT UNSIGNED NOT NULL,
        player_id INT UNSIGNED NOT NULL,
        response ENUM('pendent','confirmat','rebutjat') NOT NULL DEFAULT 'pendent',
        response_note VARCHAR(255) NULL,
        responded_at DATETIME NULL,
        PRIMARY KEY (callup_id, player_id),
        CONSTRAINT fk_cp_callup FOREIGN KEY (callup_id) REFERENCES callups(id) ON DELETE CASCADE,
        CONSTRAINT fk_cp_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    ) $t",

    "CREATE TABLE activity_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        team_id INT UNSIGNED NULL,
        type VARCHAR(40) NOT NULL,
        description VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL,
        KEY idx_activity_date (created_at),
        CONSTRAINT fk_act_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_act_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
    ) $t",

    "CREATE TABLE login_attempts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        email VARCHAR(190) NOT NULL,
        attempted_at DATETIME NOT NULL,
        KEY idx_login_ip (ip, attempted_at),
        KEY idx_login_email (email, attempted_at)
    ) $t",

    // Categories habituals de l'hoquei patins català
    "INSERT INTO categories (name, min_age, max_age, sort_order) VALUES
        ('Escoleta', 4, 6, 10), ('Prebenjamí', 7, 8, 20), ('Benjamí', 9, 10, 30),
        ('Aleví', 11, 12, 40), ('Infantil', 13, 14, 50), ('Juvenil', 15, 16, 60),
        ('Júnior', 17, 19, 70), ('Sènior', 20, NULL, 80), ('Veterans', 35, NULL, 90)",
];
