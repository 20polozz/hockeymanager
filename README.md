# HockeyManager

Aplicació web de gestió d'un club d'hoquei patins (coordinadors, entrenadors i jugadors).
Viu temporalment a `hockeyfantasy.eu/hockeymanager`, però és **totalment independent**:
base de dades pròpia, sessió pròpia i cap fitxer compartit amb HockeyFantasy.

## Tecnologia

- PHP 7.4+ sense frameworks ni Composer (funciona a l'hosting compartit de cdmon).
- MariaDB / MySQL amb PDO i consultes preparades.
- Estructura MVC pròpia: router, controladors, repositoris, vistes i components.
- HTML + CSS + JS propis (sense dependències externes).

```
hockeymanager/
├── index.php              Front controller (totes les peticions)
├── .htaccess              Reescriptura i bloqueig de carpetes internes
├── config.example.php     Plantilla → copiar com a config.php (no va a GitHub)
├── app/
│   ├── bootstrap.php      Autoload, errors, sessió
│   ├── routes.php         Rutes (cada una amb el seu permís)
│   ├── permissions.php    Matriu permís → rols
│   ├── navigation.php     Menú lateral
│   ├── Core/              Router, Auth, Gate, TeamScope, Database, Migrator…
│   ├── Controllers/
│   ├── Repositories/      Consultes SQL per mòdul
│   └── Views/             layouts/, partials/, components/, pages/
├── database/
│   ├── migrations/        001_core.php… (s'apliquen des de /install)
│   └── seeds/             DemoSeeder (dades fictícies, opcionals)
└── assets/                css, js, img
```

## Posar-ho en marxa (cdmon)

1. Panell de cdmon → crea una **base de dades nova** (p. ex. `hockeymanager`) amb el seu usuari.
2. Copia `config.example.php` com a `config.php`, omple-hi les dades de la BD i una
   `install_key` llarga, i puja'l per FTP a la carpeta `hockeymanager/` del servidor.
   (El desplegament automàtic de GitHub no el toca perquè no és al repositori.)
3. Obre `https://hockeyfantasy.eu/hockeymanager/install`, escriu la clau i crea el
   compte de coordinador. Opcionalment, carrega les dades de demostració.

Quan arribin noves migracions, un coordinador les aplica des de **Configuració → Base de dades**.

## Moure-ho a un domini propi

Copia la carpeta `hockeymanager/` a l'arrel del nou hosting, importa (o recrea) la BD i
actualitza `config.php`. La ruta base es detecta sola (`base_path => null`).

## Seguretat

- Permisos comprovats al servidor a cada ruta (no només amagant el menú).
- Entrenadors i jugadors només veuen els seus equips (`TeamScope`).
- CSRF a tots els formularis, contrasenyes amb `password_hash`, límit d'intents de login,
  sessió regenerada en entrar, sortida escapada, carpetes internes bloquejades.

## Fases

1. ✅ Nucli, login i rols, layout, sidebar, header, dashboard, jugadors i equips (lectura)
2. Assistència i calendari
3. Exercicis i sessions d'entrenament
4. Partits i convocatòries
5. Estadístiques (incloent porters)
6. Gestió d'usuaris/equips/jugadors i connectors d'importació (FECAPA, RFEP…)
