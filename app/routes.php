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
$router->post('/jugadors/{id}/absencies', 'AbsenceController@store');
$router->post('/absencies/{id}/tornada', 'AbsenceController@finish');
$router->post('/absencies/{id}/eliminar', 'AbsenceController@destroy');
$router->get('/perfil', 'PlayerController@own', ['can' => 'players.own']);

// Equips
$router->get('/equips', 'TeamController@index', ['can' => 'teams.view']);
$router->get('/equips/nou', 'TeamController@create', ['can' => 'teams.manage']);
$router->get('/equips/categories', 'CategoryController@index', ['can' => 'categories.manage']);
$router->post('/equips/categories', 'CategoryController@store', ['can' => 'categories.manage']);
$router->post('/equips/categories/{id}', 'CategoryController@update', ['can' => 'categories.manage']);
$router->post('/equips/categories/{id}/moure', 'CategoryController@move', ['can' => 'categories.manage']);
$router->post('/equips/categories/{id}/eliminar', 'CategoryController@destroy', ['can' => 'categories.manage']);
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
$router->post('/calendari/{id}/equips', 'CalendarController@teams', ['can' => 'calendar.manage']);
$router->post('/calendari/{id}/jugadores', 'CalendarController@roster', ['can' => 'calendar.manage']);
$router->post('/calendari/{id}/eliminar', 'CalendarController@destroy', ['can' => 'calendar.manage']);

// Assistència
$router->get('/assistencia', 'AttendanceController@index', ['can' => 'attendance.view']);
$router->get('/assistencia/estadistiques', 'AttendanceController@stats', ['can' => 'attendance.view']);
$router->get('/assistencia/{id}', 'AttendanceController@take', ['can' => 'attendance.manage']);
$router->post('/assistencia/{id}', 'AttendanceController@save', ['can' => 'attendance.manage']);

// Exercicis
$router->get('/exercicis', 'ExerciseController@index', ['can' => 'exercises.view']);
$router->get('/exercicis/nou', 'ExerciseController@create', ['can' => 'exercises.manage']);
$router->post('/exercicis', 'ExerciseController@store', ['can' => 'exercises.manage']);
$router->get('/exercicis/{id}', 'ExerciseController@show', ['can' => 'exercises.view']);
$router->get('/exercicis/{id}/editar', 'ExerciseController@edit', ['can' => 'exercises.manage']);
$router->post('/exercicis/{id}', 'ExerciseController@update', ['can' => 'exercises.manage']);
$router->post('/exercicis/{id}/eliminar', 'ExerciseController@destroy', ['can' => 'exercises.manage']);
$router->post('/exercicis/{id}/preferit', 'ExerciseController@favorite', ['can' => 'exercises.view']);

// Entrenaments (sessions)
$router->get('/entrenaments', 'SessionController@index', ['can' => 'trainings.view']);
$router->get('/entrenaments/nou', 'SessionController@create', ['can' => 'trainings.manage']);
$router->post('/entrenaments', 'SessionController@store', ['can' => 'trainings.manage']);
$router->get('/entrenaments/{id}', 'SessionController@show', ['can' => 'trainings.view']);
$router->get('/entrenaments/{id}/editar', 'SessionController@edit', ['can' => 'trainings.manage']);
$router->post('/entrenaments/{id}', 'SessionController@update', ['can' => 'trainings.manage']);
$router->post('/entrenaments/{id}/eliminar', 'SessionController@destroy', ['can' => 'trainings.manage']);
$router->post('/entrenaments/{id}/exercicis', 'SessionController@items', ['can' => 'trainings.manage']);


// Usuaris
$router->get('/usuaris', 'UserController@index', ['can' => 'users.manage']);
$router->get('/usuaris/nou', 'UserController@create', ['can' => 'users.manage']);
$router->post('/usuaris', 'UserController@store', ['can' => 'users.manage']);
$router->get('/usuaris/{id}/editar', 'UserController@edit', ['can' => 'users.manage']);
$router->post('/usuaris/{id}', 'UserController@update', ['can' => 'users.manage']);
$router->post('/usuaris/{id}/eliminar', 'UserController@destroy', ['can' => 'users.manage']);
$router->post('/usuaris/{id}/estat', 'UserController@toggle', ['can' => 'users.manage']);


// Configuració del compte
$router->get('/configuracio', 'SettingsController@index', ['can' => 'settings.view']);
$router->post('/configuracio/contrasenya', 'SettingsController@updatePassword', ['can' => 'settings.view']);

// Mòduls de les properes fases (pàgina pròpia amb l'estat i el que inclourà)
