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
- **`validacion.js`** defines `initDrawerValidation()` — a named function called from `loadDrawer()` after the drawer HTML is injected. It binds submit/input validation to `.drawer-form`. Uses `dataset.validationBound` guard to prevent duplicate binding across SPA navigations. On validation failure, calls `e.stopPropagation()` to prevent the delegated `clientes.js` submit handler from firing. Also exports `window.limpiarErroresDrawer` and `initDrawerPage()`.
- **Real-time input sanitization in `initDrawerValidation()`**: The `input` event handler strips invalid characters as the user types. Pattern:
  ```js
  if (regla === "soloLetras") {
    input.value = input.value.replace(/[^a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]/g, "");
  } else if (regla === "soloNumeros" || regla === "telefono") {
    input.value = input.value.replace(/\D/g, "");
  }
  ```
  To add sanitization to new drawers, update the `input` listener in `initDrawerValidation()` with additional `regla` branches. Currently implemented for clientes (campos nombre, apellido, celular, DNI). Pendiente: aplicar en empleados y reservas drawers.
- **`initDrawerPage()`**: `loadPage()` calls this after `loadDrawer()` completes. Define it globally to run page-specific logic. No longer used with the new API-driven approach (clientes/empleados handle forms inline).
- **Data-table modules** (clientes, empleados, reservas): each defines a global `cargar{Modulo}()` function and a uniquely-named `renderTabla{Modulo}()` function. `loadPage()` step 9 calls the appropriate `cargar{Modulo}()` after scripts are injected and `initTable()`/`initColumnPicker()` have run. The `renderTabla{Modulo}()` function calls `initTable()` internally to re-bind search/sort after repopulating the tbody. **IMPORTANT**: `renderTabla` must NOT be used as a bare global name — it collides across modules since the script dedup prevents re-loading (e.g., `empleados.js` overwrites `window.renderTabla`, breaking clientes on return navigation). Each module must use a unique name.
- **`cargarReservas()`** must be called from `loadPage()` step 9 (not just from the modul's own script), because `reservas.js` is preloaded from `inicio/index.html` and the SPA navigation re-injects it, but the function only auto-runs if explicitly called.
- **`await` async page init functions** in `loadPage()` step 9 (`cargarClientes`, `cargarEmpleados`, `cargarReservas`). Without `await`, a subsequent SPA navigation can race ahead before the previous page's data fetch completes, corrupting global state. All three must be awaited.
- **No `DOMContentLoaded` listener** in data-module scripts (`clientes.js`, `empleados.js`, etc.). The module functions are called from `loadPage()` step 9 only; an extra `DOMContentLoaded` listener creates a race condition (two concurrent calls writing to the same global data variable).
- **⚠️ `loadPage` fetch cache buster**: `loadPage()` MUST use `?v=Date.now()` on the HTML fetch (`index.html?v=...`). Without a cache buster, the browser serves a cached version of `index.html` to the fetch call (even on Ctrl+F5), which means any recent HTML edits are silently reverted when `loadPage` replaces `.main-content` via `innerHTML`. This caused the "checkbox appears momentarily then disappears" bug in `reservas/`. The previous `v=` cache buster ban was wrong — the concern about race conditions applies to AbortController, not to simple cache busting on a static HTML fetch.
- **Never add AbortController or debug bars (`#router-debug`)** to `loadPage()` — HTML fetch abort logic introduces race conditions, and persistent debug elements pollute the DOM across SPA navigations.
- **`#toast-container` must be outside `.main-content`**: Same reason as the drawer — `loadPage()` replaces `.main-content` via `innerHTML`, so toasts placed inside get destroyed on SPA navigation. Always place them after `</main>`.
- **Toolbar filter elements need CSS**: Checkboxes in `.table-toolbar` (e.g., `.filter-pendientes`, `.filter-fecha`) must have explicit `display: flex; align-items: center; white-space: nowrap` CSS. Without `white-space: nowrap`, the flex container may shrink the label to near-zero width, making it appear invisible. See `reservas/reservas.css` for reference. Also, the filter labels (e.g., "Desde"/"Hasta" in `.filter-date`) must use `color: var(--text-primary)`, NOT `--text-secondary`, so the text doesn't render gray/muted in the dark theme — `pagos.css` had this bug (fixed 2026-08-28), reservas/turnos use `--text-primary` correctly.
- **`initFiltroCanceladas()` in reservas**: Called from `renderTablaReservas()`. Binds a simple checkbox `#showCanceladas` (default off = hide canceled rows via `.inactive` badge, checked = show all) and is combined with the Desde/Hasta date-range filters and the limit selector in a single `aplicarFiltros()` function. Guards with `chk.dataset.bound`. The old 3-state cycle button (Mostrar/Mostrando/Solo canceladas) and the `showSoloPendientes` / `showSoloHoy` filters were removed (2026-08-28).
- **Paginación en clientes** (`clientes/clientes.js`):
  - `clientesPaginaActual` (global) + `initPaginacionClientes()`, `aplicarFiltrosClientes()`, `calcularTotalPaginas()`.
  - `initPaginacionClientes()` se ejecuta una sola vez (`toolbar.dataset.paginacionBound`). Inyecta el limit-selector (10/20/50/Todos) en `.table-toolbar` antes de `.column-picker-btn` (o al final si no existe aún), y los botones ←/→ con indicador en `#paginacionBar`. También bindea `input` en `#tableSearch` y `change` en `#showInactivos` para resetear a página 1 y re-aplicar.
  - `aplicarFiltrosClientes()`: remueve `.pag-hidden` de todas las filas, cuenta las visibles (`style.display !== "none"`), oculta con `.pag-hidden` (CSS `display: none !important`) las fuera del slice actual. Si limit es 0 (Todos), oculta la barra de paginación.
  - `.pag-hidden` se usa en vez de `style.display` para no pisar el filtro de `filterRows()` (en `table.js`), que usa `style.display` directamente. El orden de ejecución garantiza que `filterRows()` corre primero (por `initTable()`) y `aplicarFiltrosClientes()` después.
  - El limit-selector y paginación-bar se vinculan a los datos que YA están en el DOM (no re-fetch). La barra tiene `max-width: 600px` y está centrada.
  - Para aplicar paginación a otro módulo, copiar el patrón: variable global de página, función init (guard `dataset.paginacionBound`), función aplicar que itera filas visibles y usa clase ocultante, y bindear los eventos de búsqueda/filtro para resetear página.

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
│   ├── reservas/             # Pagos y reservas con tabla JS + API
│   ├── pagos/                # Todos los pagos individuales (JS + API)
│   └── ajustes/              # Ajustes de cuenta (API-driven)
├── api/                      # Backend JSON endpoints
│   ├── login.php             # POST: autenticación
│   ├── logout.php            # session_destroy + redirect
│   ├── recuperar.php         # Password reset via PHPMailer
│   ├── usuario.php           # GET: current user info + isAdmin
│   ├── clientes.php          # CRUD: listar, crear, editar, toggle_estado
│   ├── empleados.php         # CRUD: listar, crear, editar, toggle_estado
│   ├── ajustes.php           # check_usuario, cambio_usuario, cambio_contrasena
│   ├── pagos.php             # listar pagos individuales, factura_detalle
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
- **Ajustes: validación de datos editables** (`api/ajustes.php` + `initAjustes()` en `layout.js`): Los handlers `cambio_dni`, `cambio_email`, `cambio_celular`, `cambio_nombre` y `cambio_usuario` deben validar formato y unicidad para que coincidan con la validación de empleados (`api/empleados.php` `validarDatos()` + `verificarDuplicados()`). Reglas actuales:
  - **DNI**: formato `\d{7,8}` + unicidad contra `usuarios` (excluyendo el propio `usu_id`) y `clientes`.
  - **Email**: formato válido + unicidad contra `usuarios` (excluyendo el propio) y `clientes`.
  - **Celular**: solo dígitos y entre 7 y 10 caracteres.
  - **Nombre/apellido**: solo letras (incluyendo acentos y ñ) y al menos 2 caracteres.
  - **Usuario**: alfanumérico (letras, números, `_`) de 3 a 20 caracteres.
  Para cambios futuros: si se agrega una validación de formato/unicidad en empleados, replicarla en ajustes (y viceversa). El frontend (patrones `pattern`/`maxlength` HTML + checks JS en `layout.js`) debe ir alineado con el backend. Los mensajes de error del backend ya se muestran al usuario vía `mostrarToast`.
- **`declare(strict_types=1)`** in `config/init.php` — prevents automatic type coercion.
- **Output escaping**: `htmlspecialchars()` on all dynamic output. Prepared statements via PDO for all queries.
- **BASE_URL**: computed automatically in `layout.js` from the script's `src` attribute. No hardcoding needed across environments.
- **API fetch URLs**: ALL `fetch` calls to backend endpoints MUST use `BASE_URL + "/api/..."` (absolute) — never relative paths like `"../api/..."`. Relative paths break after F5 refresh because the page URL context changes (direct page load vs SPA navigation). This was fixed in clientes, empleados, and reservas modules.
- **Deploy**: `deploy.sh` — cron copies `servidor/` to `/var/www/html/` via `rsync --delete`, excludes `.git`. Backups kept in `/var/www/backups/`.

## Git y branches

- **`main` es la única fuente de verdad** (2026-08-28: su contenido pasó a ser la branch `sportaV0.8.9` de Sebas, absorbida vía merge `-s ours`).
- **`sportaV0.8.9` quedó congelada**: ruleset de GitHub "sportaV0.8.9 solo lectura" bloquea push, force-push, borrado y recreación (sin bypass). **No pushear nada más ahí**.
- Todo desarrollo nuevo va directo a `main` (idealmente vía PR).

## DB

- Schema: `sporta.sql` (DDL). Seed data: `datos_iniciales.sql`.
- Users table: `usuarios`, passwords hashed with `password_hash()`.
- Default test passwords: "1234" (users: `admin`, `empleado1`).

## CSS

- Dark theme with yellow accent (`#facc15` / `#fbbf24`).
- Glassmorphism (backdrop-filter blur, semi-transparent backgrounds).
- Sidebar: fixed 260px, responsive hamburger at 768px.

## Known Bugs — "Errores críticos" (trigger phrase)

When asked to fix "Errores críticos", the following 4 bugs must be corrected:

### 1. `toISOString()` uses UTC instead of local timezone
**Files:** `recursos/js/turnos.js:22`, `recursos/js/reservas.js:194,319`
**Fix:** Replace `new Date().toISOString().split('T')[0]` with `new Date().toLocaleDateString('en-CA')` everywhere. In Argentina (UTC-3), after 9 PM local time, UTC rolls to the next day, breaking date pickers and the "Solo turnos de hoy" filter.

### 2. `gmdate('Y-m-d')` in turnos_canchas.php
**File:** `api/turnos_canchas.php:58` — `obtenerDatos()` defaults to `gmdate('Y-m-d')` (GMT), ignoring the configured `America/Argentina/Buenos_Aires` timezone.
**Fix:** Replace `gmdate('Y-m-d')` with `date('Y-m-d')`.

### 3. `reservasMetodosPago` variable overwritten
**File:** `recursos/js/reservas.js:10,114`
**Bug:** `reservasMetodosPago = data.metodos_pago` on line 10 assigns to the hoisted variable, but `var reservasMetodosPago = []` on line 114 runs later and overwrites the data with an empty array. The payment method select in the drawer ends up empty.
**Fix:** Move `var reservasMetodosPago = [];` above `cargarReservas()` (before line 3).

### 4. `cargar*()` not awaited after form submit
**Files:** `clientes/clientes.js:131`, `empleados/empleados.js:138`, `reservas/reservas.js:235,270`
**Bug:** After saving a form, the table reload function (`cargarClientes()`, etc.) is called inside a `.then()` callback without `await`. If the user navigates away quickly, the table data isn't refreshed.
**Fix:** Convert the submit event listener callbacks to `async` and use `await` on the reload calls. Also add `mostrarToast()` error feedback in the `.catch()` instead of just `console.error`.

## Pendientes (futuras correcciones)

Issues identificados en el audit de clientes, pendientes de corregir:

### 1. `conexion()` fuera del try-catch en `clientes.php`
**File:** `api/clientes.php:13`
**Bug:** `$pdo = conexion()` se ejecuta fuera del bloque try-catch. Si falla, `conexion.php` ejecuta `die()` con JSON crudo que corta la ejecución antes del catch.
**Fix:** Mover `$pdo = conexion()` dentro del try-catch, o refactorizar `conexion.php` para que lance excepción en vez de `die()`.

### 2. `toggleCliente()` catch sin toast al usuario
**File:** `clientes/clientes.js:82-83`
**Bug:** El `catch` de `toggleCliente()` solo hace `console.error(err)` sin feedback visual.
**Fix:** Agregar `mostrarToast("Error de conexión", "error")` después de `console.error(err)`.

### 3. Sin protección double-submit en formularios
**Files:** `clientes/clientes.js:102-137`, `empleados/empleados.js`, `reservas/reservas.js`
**Bug:** El botón de submit no se deshabilita durante la petición. Clics múltiples rápidos envían requests duplicadas (crea clientes duplicados, etc.).
**Fix:** Deshabilitar el botón submit con `disabled` o trackear estado `pending` con una variable global.

### 4. `openDrawer()` antes de rellenar campos en `drawer.js`
**File:** `recursos/js/drawer.js:106`
**Bug:** El handler de `.edit-btn` llama `openDrawer()` primero y luego llena los campos. Si `openDrawer()` falla (no encuentra `.drawer`), el código sigue y lanza TypeError al intentar llenar campos inexistentes.
**Fix:** Llenar campos antes de `openDrawer()`, o agregar un guard contra `null`.

### 5. `SELECT *` en listar clientes
**File:** `api/clientes.php:48`
**Bug:** `$stmt = $pdo->query("SELECT * FROM clientes ...")` devuelve 15+ columnas que el frontend jamás usa (ej: `cliente_localidad_id`, `cliente_provincia_id`, `cliente_pais_id`, `cliente_fecha_alta`).
**Fix:** Especificar solo las columnas necesarias en la consulta.

## Código extra eliminado

Código que no pedía el PO, agregado por Sebas en `sportaV0.8.9`, **eliminado por completo** en `main` (2026-08-28). NO reintroducir sin pedido explícito.

- **Logs**: `servidor/logs/` (módulo SPA), `api/logs.php`, `config/log.php`, función `loggear()` (62 llamadas en API files), ítem Logs del sidebar y dispatch en `layout.js`. Eliminado; además se removió `servidor/log/` de `.gitignore` (solo queda `*.log`).
- **Minijuego "Sporta Memory"**: bloque completo en `recursos/js/layout.js`, CSS en `recursos/css/global.css`, `recursos/css/minijuego.css`, overlay + JS en `index.php`, y la rama `'minijuego' => true` en `config/conexion.php`.
- **Easter egg Messi**: login `lionelmessi` en `api/login.php`, overlay + confeti en `index.php` y `recursos/css/layout-login.css`.

Verificación: en los catch de `index.php` ahora se muestra `#loginError` / `#recoverMsg` en vez de lanzar el minijuego.
