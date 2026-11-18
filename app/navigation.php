<?php
/**
 * Menú lateral. Cada element només es mostra si l'usuari té el permís
 * (i la ruta corresponent també el comprova al servidor).
 */
return [
    ['key' => 'dashboard',  'label' => 'Dashboard',      'path' => '/',              'icon' => 'dashboard', 'can' => 'dashboard.view'],
    ['key' => 'attendance', 'label' => 'Assistència',    'path' => '/assistencia',   'icon' => 'checklist', 'can' => 'attendance.view'],
    ['key' => 'calendar',   'label' => 'Calendari',      'path' => '/calendari',     'icon' => 'calendar',  'can' => 'calendar.view'],
    ['key' => 'exercises',  'label' => 'Exercicis',      'path' => '/exercicis',     'icon' => 'cone',      'can' => 'exercises.view'],
    ['key' => 'trainings',  'label' => 'Entrenaments',   'path' => '/entrenaments',  'icon' => 'dumbbell',  'can' => 'trainings.view'],
    ['key' => 'players',    'label' => 'Jugadors/es',       'path' => '/jugadors',      'icon' => 'users',     'can' => 'players.view'],
    ['key' => 'profile',    'label' => 'El meu perfil',  'path' => '/perfil',        'icon' => 'user',      'can' => 'players.own'],
    ['key' => 'teams',      'label' => 'Equips',         'path' => '/equips',        'icon' => 'shield',    'can' => 'teams.view'],
    ['key' => 'users',      'label' => 'Usuaris',        'path' => '/usuaris',       'icon' => 'lock',      'can' => 'users.manage'],
];
