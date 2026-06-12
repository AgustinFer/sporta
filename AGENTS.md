# Sporta — AGENTS.md

Vanilla PHP SPA (faculty project). No build step, no tests, no framework.

## Architecture

- **SPA routing**: `recursos/js/layout.js` — `loadPage(route)` does `fetch('/route/index.php')`, parses HTML, replaces `.main-content` via `innerHTML`. Scripts inside fetched HTML never re-execute.
- **Entrypoint**: `servidor/index.php` (login). Each module is `servidor/{modulo}/index.php`, renders full HTML. SPA extracts `.main-content`.
- **Drawers** (ABM panels): loaded async by `loadDrawer()` from `componentes/drawers/{modulo}.php`. Injected into `#drawer-container` (outside `.main-content`).
- **DB**: MySQL via PDO. Credentials from `.env` loaded manually by `config/env.php` (`putenv` + `$_ENV`). `config/conexion.php` returns PDO instance.
- **Auth**: `config/clases.php` — `Usuario::iniciarSesion()`, `password_hash`/`password_verify`, session-based. Rol check: `Usuario::isAdmin()`.
- **Composer**: only for PHPMailer (password recovery).

## SPA Quirks

- **Trailing slash required** in `history.pushState` URLs (e.g. `/clientes/`). Apache `mod_dir` 301 redirects `/clientes` → `/clientes/` eating POST data. Always use trailing slashes in `pushState` and `header("Location: ...")`.
- **Script loading**: each PHP page declares its own `<script>` tags. Only scripts from the **initial** page load execute. `loadPage()` never re-executes fetched scripts. To make a JS function available after SPA navigation, its `<script>` must be in the initial page, OR the function must be called explicitly from `loadPage()`.
- **`table.js`** must be loaded on every page (not just clientes) because it defines `initTable()` and `initColumnPicker()` which `loadPage()` calls after SPA navigation. Both functions guard against missing DOM elements.
- **`validacion.js`** runs as IIFE on page load. The drawer form (`.drawer-form`) doesn't exist yet (loaded later by `loadDrawer()`), so the IIFE returns early and the submit handler is **never** attached. To re-bind drawer validation after SPA nav, use a named function called from `loadPage()` / `loadDrawer()`.

## File Layout

```
servidor/
├── index.php                 # Login (POST to iniciar sesión / password recovery modal)
├── logout.php                # session_destroy + redirect
├── recuperar.php             # AJAX endpoint: password reset email via PHPMailer
├── config/
│   ├── init.php              # Bootstrap: BASE_URL, session_start, require clases
│   ├── env.php               # Manual .env loader (putenv + $_ENV)
│   ├── conexion.php          # conexion(): returns PDO
│   └── clases.php            # Persona (abstract), Cliente, Usuario (incl. login)
├── {modulo}/                 # Each: index.php + modulo.css
│   ├── inicio/               # Dashboard (reloj, clima vía open-meteo)
│   ├── clientes/             # Full ABM: drawer, table, toggle estado
│   ├── empleados/            # Full ABM (admin only)
│   ├── turnos/               # Partial (static view)
│   ├── canchas/              # Partial (admin only)
│   ├── reservas/             # Placeholder
│   └── ajustes/              # Placeholder
├── componentes/
│   ├── header.php            # Loaded via AJAX by loadComponent()
│   ├── sidebar.php           # Menu (admin items hidden via isAdmin())
│   └── drawers/{modulo}.php # ABM form panels, loaded via loadDrawer()
└── recursos/
    ├── css/                  # global.css, layout.css, drawer.css, etc.
    ├── js/
    │   ├── layout.js         # SPA router, loadPage, loadDrawer, clima/reloj
    │   ├── drawer.js         # Drawer open/close, edit pre-fill (event delegation)
    │   ├── table.js          # Search filter, sort, column picker, inactive filter
    │   └── validacion.js     # Client-side form validation
    └── img/
```

## Key Conventions

- **ABM pattern**: POST to same page → PHP processes → `header("Location: ...")` redirect (PRG). Success message in `$_SESSION['flash_success']`, error in `$_SESSION['flash_error']`.
- **`declare(strict_types=1)`** in `config/init.php` — prevents automatic type coercion.
- **Output escaping**: `htmlspecialchars()` on all dynamic output. Prepared statements via PDO for all queries.
- **BASE_URL**: defined in `config/init.php`. Empty string for root deployment, `/sporta` for local subfolder.
- **Deploy**: `deploy.sh` — cron copies `servidor/` to `/var/www/html/` via `rsync --delete`, excludes `.git`. Backups kept in `/var/www/backups/`.

## DB

- Schema: `sporta.sql` (DDL). Seed data: `datos_iniciales.sql`.
- Users table: `usuarios`, passwords hashed with `password_hash()`.
- Default test passwords: "1234" (users: `admin`, `empleado1`).

## CSS

- Dark theme with yellow accent (`#facc15` / `#fbbf24`).
- Glassmorphism (backdrop-filter blur, semi-transparent backgrounds).
- Sidebar: fixed 260px, responsive hamburger at 768px.
