# HockeyManager

Aplicació web de gestió d'un club d'hoquei patins (coordinadors, entrenadors i jugadors).
Projecte independent: base de dades, sessió i domini propis.

## Tecnologia

- PHP 7.4+ sense frameworks ni Composer (funciona a l'hosting compartit de cdmon).
- MariaDB / MySQL amb PDO i consultes preparades.
- Estructura MVC pròpia: router, controladors, repositoris, vistes i components.
- HTML + CSS + JS propis (sense dependències externes).

```
(arrel del repositori = arrel del domini)
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
│   ├── migrations/        001_core.php… (s'apliquen soles)
│   └── seeds/             DemoSeeder (dades fictícies, opcionals)
└── assets/                css, js, img
```

## Posar-ho en marxa en un hosting nou

1. **Base de dades**: crea una BD MySQL/MariaDB i un usuari amb permisos. Si ja tens dades,
   importa-hi el bolcat (`.sql`) des de phpMyAdmin.
2. **config.php**: copia `config.example.php` com a `config.php` **al servidor** (per FTP o
   des del gestor de fitxers) i omple-hi les credencials de la BD i una `install_key` llarga.
   No es puja mai a GitHub.
3. **Desplegament automàtic**: a GitHub → *Settings → Secrets and variables → Actions* crea
   aquests secrets:

   | Secret | Exemple |
   |---|---|
   | `FTP_SERVER` | `ftp.elmeudomini.cat` |
   | `FTP_USERNAME` | usuari FTP |
   | `FTP_PASSWORD` | contrasenya FTP |
   | `FTP_SERVER_DIR` | carpeta pública del domini, amb `/` final (ex.: `/public_html/`) |

   Cada `push` a `main` puja només els fitxers canviats (*Actions → Desplegar a producció*).
4. **Primera instal·lació** (només si la BD és buida): obre `https://elmeudomini/install`
   amb la `install_key` per crear el primer compte.

La ruta base es detecta sola, així que funciona tant a l'arrel d'un domini com dins d'una carpeta.
El servidor ha de tenir Apache amb `mod_rewrite` (o equivalent) perquè el `.htaccess` funcioni.

## Seguretat

- Permisos comprovats al servidor a cada ruta (no només amagant el menú).
- Entrenadors i jugadors només veuen els seus equips (`TeamScope`).
- CSRF a tots els formularis, contrasenyes amb `password_hash`, límit d'intents de login,
  sessió regenerada en entrar, sortida escapada, carpetes internes bloquejades.

## Mòduls

- **Dashboard** amb resum, accés ràpid, esdeveniments, assistència, absències i activitat
- **Calendari** (mes/setmana/dia) amb entrenaments, gimnàs i esdeveniments especials, per equips o per llista de jugadores
- **Assistència**: passar llista des del mòbil i estadístiques
- **Exercicis** (biblioteca amb categories, preferits i vídeo) i **Entrenaments** (sessions)
- **Absències i baixes temporals** de jugadores (lesió, viatge…)
- **Jugadors/es** i **Equips** amb historial; **Usuaris** amb rols i equips assignats

## Actualitzacions de la BD

Les migracions noves de `database/migrations/` s'apliquen soles en la primera petició
després de desplegar (amb un bloqueig per evitar execucions simultànies).
