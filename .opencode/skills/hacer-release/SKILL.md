---
name: hacer-release
description: Genera el zip de preproducción de Sporta. Úsalo cuando pidan "generar zip", "hacer release", "empaquetar para pre", "zip 0.9.x", "release para preproducción" o cualquier versión del formato X.Y.Z. Cubre copiar servidor/ a la carpeta sporta, aplicar ajustes de preproducción (BASE_URL y desactivar recuperación) y empaquetar/verificar el zip.
---

# hacer-release — Generar zip de preproducción de Sporta

Este skill automatiza el proceso de entrega a preproducción: armar un zip
`servidor/` → carpeta `sporta/` con los ajustes de entorno aplicados y listo
para descomprimir en el servidor.

## Regla de oro

**NO modificar el working tree del repo.** Todo el trabajo se hace sobre una
copia temporal (`/tmp/opencode/sporta`). El único artefacto que queda en el
repo es el zip final. No commitear código modificado.

## Flujo recomendado

### 1. Ejecutar el script (vía rápida)

Si no hay decisiones fuera de lo común, usar el script directamente:

```bash
./hacer-release.sh <VERSION>              # ej: ./hacer-release.sh 0.9.2
./hacer-release.sh <VERSION> --base-url=/ # pre en la raíz
./hacer-release.sh <VERSION> --con-recuperar
```

Argumentos:
- `VERSION` (requerido): formato `X.Y.Z` (ej `0.9.2`).
- `--base-url=URL` (default `/sporta`): prefijo de URL de la app.
- `--sin-recuperar` (default ON): desactiva la recuperación de contraseña.
- `--con-recuperar`: la mantiene activa (anula `--sin-recuperar`).

El script: copia a temp, aplica los cambios, empaqueta excluyendo artefactos y
verifica el contenido del zip.

### 2. Qué hace cada cambio de preproducción (contexto para revisar)

- **`config/init.php`** → `define('BASE_URL', '/sporta')`. Es el prefijo usado
  por los redirects (login/logout/clases) y las URLs de assets del login. El
  `BASE_URL` de `layout.js` se autocacula desde el `src` del script, así que
  queda coherente automáticamente.
- **Recuperación desactivada** (en `index.php` del login):
  - Se elimina el botón "¿Olvidaste la contraseña?" (`#btnCambiarPass`).
  - Se elimina el bloque HTML del modal `#modalPass`.
  - Se elimina el bloque JS que lo maneja (`var modal` … hasta `</script>`),
    para evitar código huérfano que lanzaría TypeError.
  - `/api/recuperar.php` se reemplaza por un stub que responde
    `'La recuperación de contraseña no está disponible.'` (no envía mail ni
    resetea passwords).
  - Como la recuperación queda inactiva, `config/mailer.php` y `vendor/`
    (PHPMailer) ya no se usan en preproducción: se **excluyen del zip** (ver
    sección 3). Con `--con-recuperar` sí se mantienen.

### 3. Exclusiones del zip

El zip NO debe incluir: `.git`, `.env*`, `*.log`, `index(legacy-NotWorking).html`,
carpetas vacías.

- Con `--sin-recuperar` (default): tampoco incluye `vendor/`, `composer.json` ni
  `composer.lock` (PHPMailer no se usa con la recuperación desactivada).
- Con `--con-recuperar`: sí incluye `vendor/` (PHPMailer) y los composer files,
  porque la recuperación los necesita (y requiere correr `composer install` en
  el destino).
- Los SQL se incluyen si aplican.

### 4. Verificación

El script verifica automáticamente. Confirmar manualmente si se hacen pasos a
mano:
- Estructura raíz `sporta/` (carpeta renombrada, no `servidor/`).
- `init.php` con el `BASE_URL` correcto.
- Login sin referencias a `modalPass`/`btnCambiarPass`/`recoverForm` (si fue
  `--sin-recuperar`).
- Sin `.env`/`.git`/logs/legacy en el listado.

## Pasos a mano (si no se usa el script)

1. `rm -rf /tmp/opencode/sporta && mkdir -p /tmp/opencode && cp -r servidor /tmp/opencode/sporta`
2. Aplicar cambios en la copia (ver sección 2): `sed`/`perl` para
   `BASE_URL` y la limpieza de recuperación.
3. Renombrar ya hecho (la copia ya se llama `sporta`).
4. `cd /tmp/opencode && zip -r -q <dest>/sporta-<VERSION>.zip sporta -x 'sporta/.git/*' -x 'sporta/.env*' -x 'sporta/*.log' -x 'sporta/index(legacy-NotWorking).html'`
5. Limpiar: `rm -rf /tmp/opencode/sporta`.
6. Verificar (sección 4).

## Notas técnicas

- La verificación usa `command substitution` + `here-string` (`grep <<< "..."`)
  y NO tuberías con `grep -q`: con `set -o pipefail`, `grep -q` corta la
  tubería al matchear y `unzip` recibe SIGPIPE → la pipeline devuelve 141 aunque
  el patrón sí coincida.
- El login usa `declare(strict_types=1)` vía `config/init.php`; cualquier edición
  manual debe mantener el HTML/PHP válido.
