# Sporta — AGENTS.md

Vanilla PHP SPA (faculty project). No build step, no tests, no framework.

## Architecture

- **Backend/Frontend separation**: view files are `.html` (static), all backend logic lives in `api/*.php` JSON endpoints.
- **SPA routing**: `recursos/js/layout.js` — `loadPage(route)` does `fetch('/route/index.html')`, parses HTML, replaces `.main-content` via `innerHTML`. Scripts inside fetched HTML are injected dynamically.
- **Entrypoint**: `servidor/index.html` (login form). Login submits via `fetch` to `api/login.php`. After auth, redirects to `inicio/` where the SPA initializes.
- **API endpoints**: `servidor/api/` — all return JSON. Session-based auth checked on each request.
- **Drawers** (ABM panels): loaded async by `loadDrawer()` from `componentes/drawers/{modulo}.html`. Injected into `#drawer-container` (outside `.main-content`).
- **DB**: MySQL via PDO. Credentials from `.env` loaded by `config/env.php`. `config/conexion.php` returns PDO instance.
- **Auth**: `config/clases.php` — `Usuario::iniciarSesion()`, `password_hash`/`password_verify`, session-based. Rol check: `Usuario::isAdmin()`.
- **BASE_URL** computed automatically in `layout.js` from script's `src` attribute. No hardcoded paths needed.
- **Composer**: only for PHPMailer (password recovery).

## SPA Quirks

- **Trailing slash required** in `history.pushState` URLs (e.g. `/clientes/`). Apache `mod_dir` 301 redirects `/clientes` → `/clientes/` eating POST data. Always use trailing slashes in `pushState`.
- **Script loading**: each HTML page declares its own `<script>` tags. `loadPage()` injects new scripts dynamically (deduplicates by `src` attribute). To make a JS function available after SPA navigation, its `<script>` must be in the initial page, OR the function must be called explicitly from `loadPage()`.
- **`table.js`** must be loaded on every page (not just clientes) because it defines `initTable()` and `initColumnPicker()` which `loadPage()` calls after SPA navigation. Both functions guard against missing DOM elements.
- **`validacion.js`** runs as IIFE on page load. The drawer form (`.drawer-form`) doesn't exist yet (loaded later by `loadDrawer()`), so the IIFE returns early and the submit handler is **never** attached. To re-bind drawer validation after SPA nav, use a named function called from `loadPage()` / `loadDrawer()`.
- **`initDrawerPage()`**: `loadPage()` calls this after `loadDrawer()` completes. Define it globally to run page-specific logic. No longer used with the new API-driven approach (clientes/empleados handle forms inline).
- **`#toast-container` must be outside `.main-content`**: Same reason as the drawer — `loadPage()` replaces `.main-content` via `innerHTML`, so toasts placed inside get destroyed on SPA navigation. Always place them after `</main>`.

## File Layout

```
servidor/
├── index.html                # Login (POST to api/login.php, password recovery modal)
├── index.php                 # Minimal entry point (readfile index.html)
├── config/
│   ├── init.php              # Bootstrap: BASE_URL, session_start, require clases
│   ├── env.php               # Manual .env loader (putenv + $_ENV)
│   ├── conexion.php          # conexion(): returns PDO
│   └── clases.php            # Persona (abstract), Cliente, Usuario (incl. login)
├── {modulo}/                 # Each: index.html + modulo.css
│   ├── inicio/               # Dashboard (reloj, clima vía open-meteo)
│   ├── clientes/             # Full ABM: API-driven, JS-rendered table
│   ├── empleados/            # Full ABM (admin only, API-driven)
│   ├── turnos/               # Grilla horaria (JS + API)
│   ├── canchas/              # Burbujas CRUD (JS + API)
│   ├── reservas/             # Placeholder
│   └── ajustes/              # Ajustes de cuenta (API-driven)
├── api/                      # Backend JSON endpoints
│   ├── login.php             # POST: autenticación
│   ├── logout.php            # session_destroy + redirect
│   ├── recuperar.php         # Password reset via PHPMailer
│   ├── usuario.php           # GET: current user info + isAdmin
│   ├── clientes.php          # CRUD: listar, crear, editar, toggle_estado
│   ├── empleados.php         # CRUD: listar, crear, editar, toggle_estado
│   ├── ajustes.php           # check_usuario, cambio_usuario, cambio_contrasena
│   └── turnos_canchas.php    # Turnos/reservas/canchas data (existing)
├── componentes/
│   ├── header.html           # Loaded via AJAX by loadComponent()
│   ├── sidebar.html          # Menu (admin items hidden via JS)
│   └── drawers/{modulo}.html # ABM form panels, loaded via loadDrawer()
└── recursos/
    ├── css/                  # global.css, layout.css, drawer.css, etc.
    ├── js/
    │   ├── layout.js         # SPA router, loadPage, loadDrawer, clima/reloj
    │   ├── drawer.js         # Drawer open/close, edit pre-fill (event delegation)
    │   ├── table.js          # Search filter, sort (▲/▼ indicators), column picker, inactive filter
    │   └── validacion.js     # Client-side form validation + initDrawerPage()
    └── img/
```

## Key Conventions

- **ABM pattern**: POST to same page → PHP processes → `header("Location: ...")` redirect (PRG). Success message in `$_SESSION['flash_success']`, error in `$_SESSION['flash_error']`. The toast container must handle both; errors use `.toast-error` (red), success uses `.toast-success` (green).
- **Form data preservation on error**: When validation fails on create/edit, save `$_SESSION['form_data']` before redirect. The page outputs `<script>var formData = ...</script>` (outside `.main-content`). `initDrawerPage()` reads it, opens the drawer, pre-fills fields, and clears errors. Currently implemented in `empleados/`.
- **`declare(strict_types=1)`** in `config/init.php` — prevents automatic type coercion.
- **Output escaping**: `htmlspecialchars()` on all dynamic output. Prepared statements via PDO for all queries.
- **BASE_URL**: computed automatically in `layout.js` from the script's `src` attribute. No hardcoding needed across environments.
- **Deploy**: `deploy.sh` — cron copies `servidor/` to `/var/www/html/` via `rsync --delete`, excludes `.git`. Backups kept in `/var/www/backups/`.

## DB

- Schema: `sporta.sql` (DDL). Seed data: `datos_iniciales.sql`.
- Users table: `usuarios`, passwords hashed with `password_hash()`.
- Default test passwords: "1234" (users: `admin`, `empleado1`).

## CSS

- Dark theme with yellow accent (`#facc15` / `#fbbf24`).
- Glassmorphism (backdrop-filter blur, semi-transparent backgrounds).
- Sidebar: fixed 260px, responsive hamburger at 768px.
