# VibeSync — PHP / MySQL Edition

A converted version of the static VibeSync HTML prototype that now runs on a
real PHP backend with a MySQL database. The visual design (dark mode,
glassmorphism, the `#ddb7ff / #adc6ff / #ffb0cd` colour system, Sora + Inter
fonts and Material Symbols) is unchanged — only the data layer has changed.

## Requirements

- PHP **8.0+** with `pdo_mysql` enabled (XAMPP, MAMP, Laragon, Docker, …)
- MySQL **5.7+** or MariaDB **10.4+**
- Any modern browser

## Folder structure

```
VibeSync-php/
├── index.php              splash page
├── login.php / register.php / logout.php
├── home.php, map.php, schedule.php, artists.php, queues.php, profile.php
├── includes/
│   ├── config.php          ← gitignored, holds DB credentials
│   ├── config.example.php  ← committed template
│   ├── db.php              PDO connection & dbQuery() helper
│   ├── auth.php            session, register/login, CSRF, e()
│   ├── data.php            shared SELECT helpers used by views
│   ├── header.php          shared <head> + top app bar
│   ├── nav.php             shared bottom nav + closing </body>
│   └── modal_artist.php    re-usable artist detail modal markup
├── api/
│   ├── _bootstrap.php      common: JSON, auth check, input parsing
│   ├── toggle-favorite.php
│   ├── toggle-schedule.php
│   ├── update-setting.php
│   └── search.php
├── sql/
│   ├── schema.sql          tables + base seed data
│   └── seed-demo-user.php  creates demo login (run once)
├── css/style.css           unchanged from the HTML version
└── js/app.js               rewritten to use fetch() instead of localStorage
```

## Setup (XAMPP / MAMP)

### 1. Drop the project into your web root

- **XAMPP (Windows/Mac):** copy this `VibeSync-php/` folder into
  `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac).
- **MAMP (Mac):** copy it into `/Applications/MAMP/htdocs/`.
- Start Apache and MySQL from the XAMPP/MAMP control panel.

### 2. Create the database & user

Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and run, in the SQL tab:

```sql
CREATE DATABASE vibesync_database
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'vibesync'@'localhost' IDENTIFIED BY 'Vibesync1';
GRANT ALL PRIVILEGES ON vibesync_database.* TO 'vibesync'@'localhost';
FLUSH PRIVILEGES;
```

(If you already have a different DB user, leave it as-is and just point
`includes/config.php` at it.)

### 3. Import the schema

In phpMyAdmin, select the new `vibesync_database`, click **Import**, choose
`sql/schema.sql`, and run it. This creates all tables and adds stages,
artists, performances and live queue rows.

### 4. Create the demo account

Visit:

```
http://localhost/VibeSync-php/sql/seed-demo-user.php
```

…once. The script creates a demo user with a properly hashed password and
exits.

### 5. Open the app

```
http://localhost/VibeSync-php/
```

You can either:

- **Register a new account** via the *Get Started* button → `register.php`, or
- **Log in with the demo user**:
    - Email: `alex@vibesync.app`
    - Password: `vibesync`

## Configuration

All credentials live in `includes/config.php` (already created with the
defaults the brief asked for):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vibesync_database');
define('DB_USER', 'vibesync');
define('DB_PASS', 'Vibesync1');
```

`includes/config.php` is in `.gitignore`. New environments should copy
`includes/config.example.php` to `includes/config.php` and fill it in.

Set `define('APP_DEBUG', true);` in `config.php` while developing if you want
to see PDO error messages on screen. Leave it `false` in any deployed
environment so users never see a stack trace.

## What's new compared to the HTML prototype

| Concern              | Before (HTML)                             | Now (PHP)                                                   |
|----------------------|-------------------------------------------|-------------------------------------------------------------|
| Favorites            | `localStorage`                            | `favorites` table, `POST api/toggle-favorite.php`           |
| Schedule             | `localStorage`                            | `user_schedule` table, `POST api/toggle-schedule.php`       |
| Settings (toggles)   | `localStorage`                            | `user_settings` table, `POST api/update-setting.php`        |
| Auth                 | None — `Get Started` linked to home       | Sessions, `password_hash()`, `requireLogin()`, CSRF on forms |
| Artists / lineup     | Hard-coded HTML                           | `SELECT … ORDER BY vibe_match DESC`                         |
| Now playing / Up Next| Hard-coded HTML                           | Computed live from `performances.start_time`                |
| Queues               | Hard-coded cards                          | `queues` table                                              |

The bottom nav, top bar, modal markup, chips, tabs, day selector, search,
toasts and animations are all preserved verbatim. The CSS file is untouched.

## Security notes

- **Prepared statements** for every query (`dbQuery($sql, $params)` in
  `includes/db.php`).
- **Output escaping** with the `e()` helper (`htmlspecialchars` with
  `ENT_QUOTES` and UTF-8) on every dynamic value.
- **CSRF** tokens via `csrfField()` / `verifyCsrf()` on the login & register
  forms; AJAX endpoints rely on the session cookie + `same-origin` fetches.
- **Sessions** are regenerated after a successful login (`session_regenerate_id(true)`).
- **Passwords** are stored using `password_hash($pw, PASSWORD_DEFAULT)` and
  verified with `password_verify()`.
- **Errors**: PDO exceptions are logged via `error_log()`; users only ever
  see a generic “Something went wrong, please try again.” message unless
  `APP_DEBUG` is enabled.

## Troubleshooting

- *"Database connection failed."* → MySQL isn't running or the credentials in
  `includes/config.php` don't match what you created in step 2.
- *Browser shows a 404 on `api/...`* → the project must be served by Apache
  (or `php -S`), not opened as a `file://` URL.
- *Toggles or favourites don't persist* → make sure you're logged in
  (`<body data-logged-in="1">` should be set), and check the browser network
  tab for `api/*.php` calls returning `401`.
