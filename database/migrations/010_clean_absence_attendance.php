<?php
/**
 * Migració 010 — neteja les «justificades» que es van marcar soles a jugadores
 * que estaven de baixa aquell dia: les baixes no compten com a assistència.
 */
return [
    "DELETE a FROM attendance a
     JOIN events e ON e.id = a.event_id
     JOIN player_absences pa ON pa.player_id = a.player_id
          AND pa.starts_on <= DATE(e.starts_at) AND (pa.ends_on IS NULL OR pa.ends_on >= DATE(e.starts_at))
     WHERE a.status = 'justificat'",
];
