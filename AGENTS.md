# Sporta — AGENTS.md

Vanilla PHP SPA (faculty project). No build step, no tests, no framework.

## SPA wiring

- **SPA router**: `recursos/js/layout.js` — `loadPage(route)` fetches `/{route}/index.php`, parses HTML, replaces `.main-content`, swaps `<link id="page-style">`, and calls `initTable()`, `initColumnPicker()`, `initDrawerPage()` if they exist. Scripts from fetched pages **never** re-execute.
- **Drawers** (ABM panels): loaded async by `loadDrawer()` from `componentes/drawers/{modulo}.php` into `#drawer-container`. Triggered by `document.body.dataset.drawer` set on the `<body>` element.
- **Sidebar**: loaded via `loadComponent()` from `componentes/sidebar.php`. Admin-only items (canchas, empleados) hidden via `Usuario::isAdmin()`.
- **Entrypoint**: `servidor/index.php` (login). Each module is `servidor/{modulo}/index.php` rendering full HTML.

## SPA quirks (common mistakes)

- **Trailing slash required** in `history.pushState` URLs (e.g., `/clientes/`). Apache `mod_dir` 301 redirects `/clientes` → `/clientes/` eating POST data. Always use trailing slashes in `pushState` and `header("Location: ...")`.
- **Sidebar hrefs lack trailing slash** (e.g., `/clientes`). The JS click handler intercepts and adds it via `pushState`. A full page load (JS fails) hits the 301 redirect — data loss possible on form submits.
- No `.htaccess` in repo — trailing-slash behavior is pure Apache `mod_dir`.
- **`table.js`** must be loaded on every page because `loadPage()` calls `initTable()` / `initColumnPicker()` after navigation. Both guard against missing DOM.
- **`validacion.js`** runs as IIFE and returns early if `.drawer-form` isn't in DOM (it's loaded later by `loadDrawer()`). To re-bind, expose a named init function and call it from `loadDrawer()` or a `data-drawer` listener.
- **`validacion.js`** exposes `window.limpiarErroresDrawer()` — `drawer.js` calls it on FAB (new) click.
- **`initDrawerPage()`** called by `loadPage()` at `layout.js:277` if defined — use this to run page-specific drawer setup.

## Architecture

- **DB**: MySQL via PDO. `.env` loaded by `config/env.php` (`putenv` + `$_ENV`). `config/conexion.php` returns PDO instance. `.env` sits at repo root (2 levels up from `servidor/config/`).
- **Auth**: `config/clases.php` — `Usuario::iniciarSesion()` uses `password_verify`. Session-based. Rol check: `Usuario::isAdmin()`.
- **Composer**: only for PHPMailer (password recovery via `recuperar.php`). `recuperar.php` requires `vendor/autoload.php` directly and does NOT use `config/init.php`.
- **ABM pattern (PRG)**: POST to same page → PHP processes → `header("Location: ...")` redirect. Flash messages in `$_SESSION['flash_success']` / `$_SESSION['flash_error']`. Success flashes render as auto-dismiss toast.
- **New employees** default to `password_hash("1234", PASSWORD_DEFAULT)`.
- **Seed SQL** (`datos_iniciales.sql`) stores passwords as **plaintext** `'1234'` — not hashed. Importing this file will produce non-working logins. Hash them manually or use the app to create users.

## Deploy

- `deploy.sh` lives at `/usr/local/bin/deploy.sh` on the server. Cron job runs it periodically. It fetches origin, resets to `origin/main`, rsyncs `servidor/` to `/var/www/html/` (excludes `.git`), and keeps 5 backups in `/var/www/backups/`.
- Dev vs prod: toggle `BASE_URL` in `config/init.php` — empty string for root, `/sporta` for subfolder.
