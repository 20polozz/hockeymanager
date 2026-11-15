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

Mentre viu dins de hockeyfantasy.eu **no cal cap config.php propi**: si no n'hi ha,
l'app agafa el servidor, l'usuari i la contrasenya del `config.php` de HockeyFantasy
i es connecta a la base de dades **`hockeymanager`** (la seva, no la de HockeyFantasy).

1. Panell de cdmon → comprova que la BD `hockeymanager` existeix i que l'usuari de
   HockeyFantasy hi té permisos.
2. Fusiona la PR (es desplega sola per FTP).
3. Obre `https://hockeyfantasy.eu/hockeymanager/install`. La clau d'instal·lació és
   l'`ADMIN_KEY` del `config.php` de HockeyFantasy. Crea el compte de coordinador i,
   si vols, carrega les dades de demostració.

Quan arribin noves migracions, un coordinador les aplica des de **Configuració → Base de dades**.

## Moure-ho a un domini propi

Copia la carpeta `hockeymanager/` a l'arrel del nou hosting, importa la BD i crea
`config.php` a partir de `config.example.php` amb les noves credencials. La ruta base es detecta sola (`base_path => null`).

## Seguretat

- Permisos comprovats al servidor a cada ruta (no només amagant el menú).
- Entrenadors i jugadors només veuen els seus equips (`TeamScope`).
- CSRF a tots els formularis, contrasenyes amb `password_hash`, límit d'intents de login,
  sessió regenerada en entrar, sortida escapada, carpetes internes bloquejades.

## Mòduls

- **Dashboard** amb resum, accés ràpid, esdeveniments, resultats, assistència i activitat
- **Calendari** (mes/setmana/dia) amb entrenaments recurrents i esdeveniments especials
- **Assistència**: passar llista des del mòbil i estadístiques
- **Exercicis** (biblioteca amb categories, preferits i vídeo) i **Entrenaments** (sessions)
- **Partits**, **acta** (participació i estadístiques, també de porteria) i **Convocatòries**
- **Estadístiques** d'equip, jugadors/es i porters/es (només dades registrades)
- **Jugadors/es** i **Equips** amb historial; **Usuaris** amb rols i equips assignats

## Actualitzacions de la BD

Les migracions noves de `database/migrations/` s'apliquen soles en la primera petició
després de desplegar (amb un bloqueig per evitar execucions simultànies).
