<?php
/**
 * Rutes de HockeyManager.
 * @var App\Core\Router $router
 */

// Instal·lació i actualització de la BD (el controlador fa les seves comprovacions)
$router->get('/install', 'InstallController@show', ['auth' => false]);
$router->post('/install', 'InstallController@run', ['auth' => false]);

// Sessió
$router->get('/login', 'AuthController@showLogin', ['guest' => true]);
$router->post('/login', 'AuthController@login', ['guest' => true]);
$router->post('/logout', 'AuthController@logout');

// Dashboard i elements globals de la barra superior
$router->get('/', 'DashboardController@index', ['can' => 'dashboard.view']);
$router->get('/cerca', 'SearchController@index', ['can' => 'dashboard.view']);

// Jugadors/es
$router->get('/jugadors', 'PlayerController@index', ['can' => 'players.view']);
$router->get('/jugadors/nou', 'PlayerController@create', ['can' => 'players.manage']);
$router->post('/jugadors', 'PlayerController@store', ['can' => 'players.manage']);
$router->get('/jugadors/{id}', 'PlayerController@show', ['can' => 'players.view']);
$router->get('/jugadors/{id}/editar', 'PlayerController@edit', ['can' => 'players.manage']);
$router->post('/jugadors/{id}', 'PlayerController@update', ['can' => 'players.manage']);
$router->post('/jugadors/{id}/baixa', 'PlayerController@deactivate', ['can' => 'players.manage']);
$router->get('/perfil', 'PlayerController@own', ['can' => 'players.own']);

// Equips
$router->get('/equips', 'TeamController@index', ['can' => 'teams.view']);
$router->get('/equips/nou', 'TeamController@create', ['can' => 'teams.manage']);
$router->post('/equips', 'TeamController@store', ['can' => 'teams.manage']);
$router->get('/equips/{id}/editar', 'TeamController@edit', ['can' => 'teams.manage']);
$router->post('/equips/{id}', 'TeamController@update', ['can' => 'teams.manage']);
$router->post('/equips/{id}/eliminar', 'TeamController@destroy', ['can' => 'teams.manage']);

// Calendari
$router->get('/calendari', 'CalendarController@index', ['can' => 'calendar.view']);
$router->get('/calendari/nou', 'CalendarController@create', ['can' => 'calendar.manage']);
$router->post('/calendari', 'CalendarController@store', ['can' => 'calendar.manage']);
$router->get('/calendari/{id}', 'CalendarController@show', ['can' => 'calendar.view']);
$router->get('/calendari/{id}/editar', 'CalendarController@edit', ['can' => 'calendar.manage']);
$router->post('/calendari/{id}', 'CalendarController@update', ['can' => 'calendar.manage']);
$router->post('/calendari/{id}/eliminar', 'CalendarController@destroy', ['can' => 'calendar.manage']);

// Assistència
$router->get('/assistencia', 'AttendanceController@index', ['can' => 'attendance.view']);
$router->get('/assistencia/estadistiques', 'AttendanceController@stats', ['can' => 'attendance.view']);
$router->get('/assistencia/{id}', 'AttendanceController@take', ['can' => 'attendance.manage']);
$router->post('/assistencia/{id}', 'AttendanceController@save', ['can' => 'attendance.manage']);

// Configuració del compte
$router->get('/configuracio', 'SettingsController@index', ['can' => 'settings.view']);
$router->post('/configuracio/contrasenya', 'SettingsController@updatePassword', ['can' => 'settings.view']);

// Mòduls de les properes fases (pàgina pròpia amb l'estat i el que inclourà)
$router->get('/exercicis', 'ModuleController@exercises', ['can' => 'exercises.view']);
$router->get('/entrenaments', 'ModuleController@trainings', ['can' => 'trainings.view']);
$router->get('/partits', 'ModuleController@matches', ['can' => 'matches.view']);
$router->get('/convocatories', 'ModuleController@callups', ['can' => 'callups.view']);
$router->get('/estadistiques', 'ModuleController@stats', ['can' => 'stats.view']);
