<?php
/**
 * Migració 006 — grups d'entrenament (p. ex. grups de gimnàs) que barregen
 * jugadors/es de diferents equips. Funcionen com un equip per al calendari i
 * l'assistència, però no participen en partits ni convocatòries.
 */
return [
    "ALTER TABLE teams ADD COLUMN IF NOT EXISTS kind ENUM('equip','grup') NOT NULL DEFAULT 'equip' AFTER name",
];
