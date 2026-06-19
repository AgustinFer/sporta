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
- **Script loading**: each PHP page declares its own `<script>` tags. Only scripts from the **initial** page load execute. `loadPage()` never re-executes fetched scripts. To make a JS function available after SPA navigation, its `<script>` must be loaded globally from every page. For example, `turnos.js`, `canchas.js`, and `reservas.js` are loaded in **every module's `index.php`** so `initTurnosPage()`/`initCanchasPage()`/`initReservasPage()` are globally available no matter which page the user F5'd on; `loadPage()` calls them when navigating to those routes. If you add a new module JS file, include it in all pages.
- **`table.js`** must be loaded on every page (not just clientes) because it defines `initTable()` and `initColumnPicker()` which `loadPage()` calls after SPA navigation. Both functions guard against missing DOM elements.
- **`validacion.js`** runs as IIFE on page load. The drawer form (`.drawer-form`) doesn't exist yet (loaded later by `loadDrawer()`), so the IIFE returns early and the submit handler is **never** attached. To re-bind drawer validation after SPA nav, call `bindDrawerValidation(formElement)` — exported globally from `validacion.js`. Currently used in `canchas/` via `initCanchasPage()` and `reservas/` via `initReservasPage()`.
- **`initDrawerPage()`**: `loadPage()` calls this after `loadDrawer()` completes. Define it globally to run page-specific logic (e.g. opening the drawer and pre-filling fields after a validation error). Currently used in `empleados/`.
- **`initReservasPage()`**: `loadPage()` calls this after `loadDrawer()` completes for route `reservas`. Pre-fills read-only display fields (cliente, cancha, fecha, horario) in the drawer info grid when the edit button is clicked, and calls `bindDrawerValidation()` on the drawer form. Defined in `reservas.js`.
- **`mostrarToast(mensaje, tipo)`**: defined globally in `layout.js`. `tipo` is `'success'` (green) or `'error'` (red). Auto-dismisses after 3s. Never define it locally in module files — use the global one.
- **`#toast-container` must be outside `.main-content`**: Same reason as the drawer — `loadPage()` replaces `.main-content` via `innerHTML`, so toasts placed inside get destroyed on SPA navigation. Always place them after `</main>`.

## File Layout

```
servidor/
├── index.php                 # Login (POST to iniciar sesión / password recovery modal)
├── auth/
│   ├── logout.php             # session_destroy + redirect
│   └── recuperar.php          # AJAX endpoint: password reset email via PHPMailer
├── config/
│   ├── init.php              # Bootstrap: BASE_URL, session_start, require clases
│   ├── env.php               # Manual .env loader (putenv + $_ENV)
│   ├── conexion.php          # conexion(): returns PDO
│   └── clases.php            # Persona (abstract), Cliente, Usuario (incl. login)
├── api/
│   └── turnos_canchas.php    # JSON endpoint: obtener/crud turnos y canchas
├── {modulo}/                 # Each: index.php + modulo.css
│   ├── inicio/               # Dashboard (reloj, clima vía open-meteo)
│   ├── clientes/             # Full ABM: drawer, table, toggle estado
│   ├── empleados/            # Full ABM (admin only)
│   ├── turnos/               # Grilla horaria dinámica con reservas (JSON API)
│   ├── canchas/              # Full ABM con burbujas (AJAX, admin only)
│   ├── reservas/             # ABM: listado, editar estado/observaciones, toggle pago (PRG)
│   └── ajustes/              # Ajustes de cuenta (usuario y contraseña)
├── componentes/
│   ├── header.php            # Loaded via AJAX by loadComponent()
│   ├── sidebar.php           # Menu (admin items hidden via isAdmin())
│   └── drawers/{modulo}.php # ABM form panels, loaded via loadDrawer()
└── recursos/
    ├── css/                  # global.css, layout.css, drawer.css, etc.
    ├── js/
    │   ├── layout.js         # SPA router, loadPage, loadDrawer, clima/reloj, mostrarToast()
    │   ├── drawer.js         # Drawer open/close, edit pre-fill (event delegation)
    │   ├── table.js          # Search filter, sort (▲/▼ indicators via sort-asc/sort-desc CSS), column picker, inactive filter
    │   ├── validacion.js     # Client-side validation + initDrawerPage() + bindDrawerValidation()
    │   ├── turnos.js         # Grilla horaria: initTurnosPage(), carga/crea/cambia estado reservas
    │   ├── canchas.js        # ABM canchas: initCanchasPage(), burbujas, CRUD via API
    │   └── reservas.js       # ABM reservas: initReservasPage(), pre-fill drawer display fields
    └── img/
        └── favicon.ico
```

## Key Conventions

- **ABM pattern (PRG)**: POST to same page → PHP processes → `header("Location: ...")` redirect. Success in `$_SESSION['flash_success']`, error in `$_SESSION['flash_error']`. Used in clientes, empleados, ajustes, reservas.
- **ABM pattern (AJAX/API)**: `fetch()` to `api/turnos_canchas.php` with JSON body `{accion, ...}`. Response is JSON `{ok, mensaje, ...}`. No PRG redirect — `mostrarToast()` handles feedback. Used in canchas and turnos.
- **Admin protection**: Always use `$_SESSION['usuario']->isAdmin()` **directly** as a gate at the top of restricted pages — never rely on hiding UI elements alone. `empleados/`, `canchas/` and API actions `crear_cancha`/`actualizar_cancha`/`eliminar_cancha` redirect/deny non-admin. No `$esAdmin` variable — use `isAdmin()` inline.
- **Table convention**: Use `class="table"` (not `data-table`), wrapped in `<div class="table-container">`. Toolbar uses `<div class="table-toolbar">` with `<input class="table-search" id="tableSearch">`. Both `<th>` and `<td>` need `data-column="name"` for the column picker. `<th>` elements need `data-sort="N"` for sorting (N = column index 0-based). All defined in `global.css` and `table.js`.
- **Drawer `<select>` styling**: `<select>` elements in `.drawer-form` need explicit `class="drawer-select"` to get border/padding/background from `drawer.css`. `<input>` gets styled automatically via `.drawer-form input`.
- **Drawer info grid**: For read-only display fields inside a drawer, use `.drawer-info-grid` (2-column CSS grid) with `.drawer-info-item` children. Define in the module's CSS file to match the form's label/value colors.
- **Form data preservation on error (PRG only)**: When validation fails on create/edit, save `$_SESSION['form_data']` before redirect. The page outputs `<script>var formData = ...</script>` (outside `.main-content`). `initDrawerPage()` reads it, opens the drawer, pre-fills fields, and clears errors. Currently implemented in `empleados/`.
- **`bindDrawerValidation(form)`**: call after dynamically loading a drawer form to attach client-side validation (submit + input listeners). Can be called multiple times — guards against double-binding via `dataset.validationBound`. Exported from `validacion.js`.
- **Output escaping**: `htmlspecialchars()` on all dynamic output in PHP. For JS-generated DOM (e.g. burbujas de canchas), use `escapeHtml()` helper or `textContent` instead of `innerHTML` concatenation.
- **`declare(strict_types=1)`** in `config/init.php` — prevents automatic type coercion.
- **Output escaping**: `htmlspecialchars()` on all dynamic output. Prepared statements via PDO for all queries.
- **BASE_URL**: defined in `config/init.php`. Empty string for root deployment, `/sporta` for local subfolder.
- **Emojis**: siempre literales (🔧, 👤, ⚙️, etc.), nunca escapes Unicode como `\uXXXX`. El código usa ~60 emojis literales y solo 3 escapes — uno era un bug.
- **Deploy**: `deploy.sh` — cron copies `servidor/` to `/var/www/html/` via `rsync --delete`, excludes `.git`. Backups kept in `/var/www/backups/`.

## DB

- Schema: `sporta.sql` (DDL). Seed data: `datos_iniciales.sql`.
- Users table: `usuarios`, passwords hashed with `password_hash()`.
- Default test passwords: "1234" (users: `admin`, `empleado1`).

## CSS

- Dark theme with yellow accent (`#facc15` / `#fbbf24`).
- Glassmorphism (backdrop-filter blur, semi-transparent backgrounds).
- Sidebar: fixed 260px, responsive hamburger at 768px.
