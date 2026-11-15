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

// Jugadors i equips
$router->get('/jugadors', 'PlayerController@index', ['can' => 'players.view']);
$router->get('/jugadors/{id}', 'PlayerController@show', ['can' => 'players.view']);
$router->get('/perfil', 'PlayerController@own', ['can' => 'players.own']);
$router->get('/equips', 'TeamController@index', ['can' => 'teams.view']);

// Configuració del compte
$router->get('/configuracio', 'SettingsController@index', ['can' => 'settings.view']);
$router->post('/configuracio/contrasenya', 'SettingsController@updatePassword', ['can' => 'settings.view']);

// Mòduls de les properes fases (pàgina pròpia amb l'estat i el que inclourà)
$router->get('/assistencia', 'ModuleController@attendance', ['can' => 'attendance.view']);
$router->get('/calendari', 'ModuleController@calendar', ['can' => 'calendar.view']);
$router->get('/exercicis', 'ModuleController@exercises', ['can' => 'exercises.view']);
$router->get('/entrenaments', 'ModuleController@trainings', ['can' => 'trainings.view']);
$router->get('/partits', 'ModuleController@matches', ['can' => 'matches.view']);
$router->get('/convocatories', 'ModuleController@callups', ['can' => 'callups.view']);
$router->get('/estadistiques', 'ModuleController@stats', ['can' => 'stats.view']);
