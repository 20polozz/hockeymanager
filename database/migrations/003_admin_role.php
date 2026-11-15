<?php
/**
 * Migració 003 — rol "admin" (administrador de l'aplicació).
 */
return [
    "ALTER TABLE users MODIFY role ENUM('admin','coordinador','entrenador','jugador') NOT NULL",
];
