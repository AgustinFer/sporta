# Sporta — AGENTS.md

Vanilla PHP SPA (faculty project). No build step, no tests, no framework.

## Architecture

- **Backend/Frontend separation**: view files are `.html` (static), all backend logic lives in `api/*.php` JSON endpoints.
- **SPA routing**: `recursos/js/layout.js` — `loadPage(route)` does `fetch('/route/index.html?v=Date.now())`, parses HTML, replaces `.main-content` via `innerHTML`. Scripts inside fetched HTML are injected dynamically.
- **Entrypoint**: `servidor/index.html` (login form). Login submits via `fetch` to `api/login.php`. After auth, redirects to `inicio/` where the SPA initializes.
- **API endpoints**: `servidor/api/` — all return JSON. Session-based auth checked on each request.
- **Drawers** (ABM panels): loaded async by `loadDrawer()` from `componentes/drawers/{modulo}.html`. Injected into `#drawer-container` (outside `.main-content`).
- **DB**: MySQL via PDO. Credentials from `.env` loaded by `config/env.php`. `config/conexion.php` returns PDO instance.
- **Auth**: `config/clases.php` — `Usuario::iniciarSesion()`, `password_hash`/`password_verify`, session-based. Rol check: `Usuario::isAdmin()`.
- **BASE_URL** computed automatically in `layout.js` from script's `src` attribute. No hardcoded paths needed.
- **Composer**: only for PHPMailer (password recovery).

## SPA Quirks

- **Trailing slash required** in `history.pushState` URLs (e.g. `/clientes/`). Apache `mod_dir` 301 redirects `/clientes` → `/clientes/` eating POST data. Always use trailing slashes in `pushState`.
- **Script loading**: each HTML page declares its own `<script>` tags in `index.html`. `loadPage()` step 7 injects scripts dynamically with an intentionally broken dedup (`document.querySelector('script[src="..."]')` checks the raw attribute value, not the resolved URL). This means scripts are ALWAYS re-injected on every SPA navigation, which resets closures and global state. **DO NOT fix this dedup** — proper dedup (normalizing URLs, checking `document.scripts`) breaks all SPA data modules because stale closures lose their binding to re-assigned globals.
- **`inicio/index.html` preloads some JS files statically** (`turnos.js`, `canchas.js`, `reservas.js`). These are loaded on initial SPA init AND again by step 7 on each navigation to their module. Because step 7 always re-injects them, the `var` declarations reset, giving fresh closures bound to the new globals. This is fragile but intentional.
- **`table.js`** must be loaded on every page (not just clientes) because it defines `initTable()` and `initColumnPicker()` which `loadPage()` calls after SPA navigation. Both functions guard against missing DOM elements.
- **`validacion.js`** runs as IIFE on page load. The drawer form (`.drawer-form`) doesn't exist yet (loaded later by `loadDrawer()`), so the IIFE returns early and the submit handler is **never** attached. To re-bind drawer validation after SPA nav, use a named function called from `loadPage()` / `loadDrawer()`.
- **`initDrawerPage()`**: `loadPage()` calls this after `loadDrawer()` completes. Define it globally to run page-specific logic. Currently used to restore `form_data` on validation errors in `empleados/`.
- **Data-table modules** (clientes, empleados, reservas): each defines a global `cargar{Modulo}()` function and a uniquely-named `renderTabla{Modulo}()` function. `loadPage()` step 9 calls the appropriate `cargar{Modulo}()` after scripts are injected and `initTable()`/`initColumnPicker()` have run. The `renderTabla{Modulo}()` function calls `initTable()` internally to re-bind search/sort after repopulating the tbody. **IMPORTANT**: `renderTabla` must NOT be used as a bare global name — it collides across modules since the script dedup prevents re-loading (e.g., `empleados.js` overwrites `window.renderTabla`, breaking clientes on return navigation). Each module must use a unique name.
- **`cargarReservas()`** must be called from `loadPage()` step 9 (not just from the modul's own script), because `reservas.js` is preloaded from `inicio/index.html` and the SPA navigation re-injects it, but the function only auto-runs if explicitly called.
- **`await` async page init functions** in `loadPage()` step 9 (`cargarClientes`, `cargarEmpleados`, `cargarReservas`). Without `await`, a subsequent SPA navigation can race ahead before the previous page's data fetch completes, corrupting global state. All three must be awaited.
- **No `DOMContentLoaded` listener** in data-module scripts (`clientes.js`, `empleados.js`, etc.). The module functions are called from `loadPage()` step 9 only; an extra `DOMContentLoaded` listener creates a race condition (two concurrent calls writing to the same global data variable).
- **⚠️ `loadPage` fetch cache buster**: `loadPage()` MUST use `?v=Date.now()` on the HTML fetch (`index.html?v=...`). Without a cache buster, the browser serves a cached version of `index.html` to the fetch call (even on Ctrl+F5), which means any recent HTML edits are silently reverted when `loadPage` replaces `.main-content` via `innerHTML`. This caused the "checkbox appears momentarily then disappears" bug in `reservas/`. The previous `v=` cache buster ban was wrong — the concern about race conditions applies to AbortController, not to simple cache busting on a static HTML fetch.
- **Never add AbortController or debug bars (`#router-debug`)** to `loadPage()` — HTML fetch abort logic introduces race conditions, and persistent debug elements pollute the DOM across SPA navigations.
- **`#toast-container` must be outside `.main-content`**: Same reason as the drawer — `loadPage()` replaces `.main-content` via `innerHTML`, so toasts placed inside get destroyed on SPA navigation. Always place them after `</main>`.
- **Toolbar filter elements need CSS**: Checkboxes in `.table-toolbar` (e.g., `.filter-pendientes`, `.filter-fecha`) must have explicit `display: flex; align-items: center; white-space: nowrap` CSS. Without `white-space: nowrap`, the flex container may shrink the label to near-zero width, making it appear invisible. See `reservas/reservas.css` for reference.
- **`initFiltroHoy()` in reservas**: Called from `renderTablaReservas()` (like `initFiltroPendientes()`). The `#showSoloHoy` checkbox filter is combined with `#showSoloPendientes` in a single `aplicarFiltros()` function that respects both filters simultaneously. Each filter function (`initFiltroPendientes`, `initFiltroHoy`) guards with `chk.dataset.bound` to avoid duplicate event binding.
- **Admin sidebar hiding timing**: `initLayout()` must hide `.menu-admin` items **after** `loadComponent('sidebar-container', ...)` resolves, not before. The sidebar DOM doesn't exist yet early in `initLayout()`, so `querySelectorAll('.menu-admin')` finds nothing if called before the component is loaded.

## File Layout

```
servidor/
├── index.html                # Login (POST to api/login.php, password recovery modal)
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
│   ├── reservas/             # Pagos y reservas con tabla JS + API
│   └── ajustes/              # Ajustes de cuenta (API-driven)
├── api/                      # Backend JSON endpoints
│   ├── login.php             # POST: autenticación
│   ├── logout.php            # session_destroy + redirect
│   ├── recuperar.php         # Password reset via PHPMailer
│   ├── usuario.php           # GET: current user info + isAdmin
│   ├── clientes.php          # CRUD: listar, crear, editar, toggle_estado
│   ├── empleados.php         # CRUD: listar, crear, editar, toggle_estado
│   ├── ajustes.php           # check_usuario, cambio_usuario, cambio_contrasena
│   ├── metodos_pago.php      # GET: métodos de pago disponibles
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
        └── favicon.ico
```

## Key Conventions

- **ABM pattern (API)**: `fetch()` to `api/{modulo}.php` with JSON body. Response is JSON `{ok, mensaje, ...}`. Toast feedback handled client-side by `mostrarToast()`.
- **Admin protection**: two layers. Server-side `isAdmin()` gates inside each admin API function. Frontend: pages with `data-admin` attribute on `<body>` trigger a redirect check in `loadPage()` — non-admin users are sent to `/inicio/`.
- **Form data preservation on error**: When validation fails on create/edit, save `$_SESSION['form_data']` before redirect. The page outputs `<script>var formData = ...</script>` (outside `.main-content`). `initDrawerPage()` reads it, opens the drawer, pre-fills fields, and clears errors. Currently implemented in `empleados/`.
- **Settings accordion pattern** (ajustes/): Each setting group uses `.settings-card.accordion` with `.accordion-header` (clickable, shows ▾) and `.accordion-body` (collapsible, `display: none` by default). Structure:
  ```html
  <div class="settings-card accordion">
    <div class="accordion-header" onclick="this.parentElement.classList.toggle('open')">
      <h3>Título</h3>
      <span class="accordion-arrow">▾</span>
    </div>
    <div class="accordion-body">
      <form id="form{Name}" novalidate>
        <div class="field">
          <label for="input_id">Etiqueta</label>
          <input type="text" id="input_id" ...>
        </div>
        <button type="submit" class="btn-settings">Guardar</button>
      </form>
    </div>
  </div>
  ```
  Required CSS: `.accordion-body { display: none; } .accordion.open .accordion-body { display: block; }` plus arrow rotation (see `ajustes/ajustes.css`). JS logic (validation, submit) goes in `initAjustes()` in `recursos/js/layout.js` — elements are found by `getElementById` and work inside hidden containers.
- **`declare(strict_types=1)`** in `config/init.php` — prevents automatic type coercion.
- **Output escaping**: `htmlspecialchars()` on all dynamic output. Prepared statements via PDO for all queries.
- **BASE_URL**: computed automatically in `layout.js` from the script's `src` attribute. No hardcoding needed across environments.
- **API fetch URLs**: ALL `fetch` calls to backend endpoints MUST use `BASE_URL + "/api/..."` (absolute) — never relative paths like `"../api/..."`. Relative paths break after F5 refresh because the page URL context changes (direct page load vs SPA navigation). This was fixed in clientes, empleados, and reservas modules.
- **Emojis**: siempre literales (🔧, 👤, ⚙️, etc.), nunca escapes Unicode como `\uXXXX`.
- **`conexion.php`**: always returns JSON with `http_response_code(500)` on DB error. No more `die("Error DB: ...")` in plain text.
- **Deploy**: `deploy.sh` — cron copies `servidor/` to `/var/www/html/` via `rsync --delete`, excludes `.git`. Backups kept in `/var/www/backups/`.

## DB

- Schema: `sporta.sql` (DDL). Seed data: `datos_iniciales.sql`.
- Users table: `usuarios`, passwords hashed with `password_hash()`.
- Default test passwords: "1234" (users: `admin`, `empleado1`).

## CSS

- Light/dark theme system via CSS custom properties (`:root` + `[data-theme="dark"]`). Dark mode enabled by user toggle in settings (`#themeToggle`) or `localStorage` default.
- Yellow accent (`#facc15` / `#fbbf24`).
- Glassmorphism (backdrop-filter blur, semi-transparent backgrounds).
- Sidebar: fixed 260px, responsive hamburger at 768px.
