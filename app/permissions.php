<?php
/**
 * Matriu de permisos: permís => rols que el tenen.
 * El router (opció 'can') i els controladors la fan servir a través de Gate.
 */
$all   = ['coordinador', 'entrenador', 'jugador'];
$staff = ['coordinador', 'entrenador'];
$coord = ['coordinador'];

return [
    'dashboard.view'    => $all,

    'attendance.view'   => $staff,
    'attendance.manage' => $staff,

    'calendar.view'     => $all,
    'calendar.manage'   => $staff,

    'exercises.view'    => $staff,
    'exercises.manage'  => $staff,

    'trainings.view'    => $staff,
    'trainings.manage'  => $staff,

    'matches.view'      => $all,
    'matches.manage'    => $staff,

    'callups.view'      => $all,
    'callups.manage'    => $staff,
    'callups.respond'   => ['jugador'],

    'players.view'      => $staff,
    'players.manage'    => $coord,
    'players.own'       => ['jugador'],

    'teams.view'        => $staff,
    'teams.manage'      => $coord,

    'stats.view'        => $all,

    'users.manage'      => $coord,
    'settings.view'     => $all,
    'system.install'    => $coord,
];
