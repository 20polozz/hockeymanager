<?php
/**
 * Migració 002 — login amb nom d'usuari (el correu passa a ser opcional).
 */
return [
    "ALTER TABLE users
        ADD COLUMN username VARCHAR(50) NULL AFTER name,
        ADD UNIQUE KEY uq_user_username (username),
        MODIFY email VARCHAR(190) NULL",
];
