#!/bin/bash
#
# hacer-release.sh — Genera el zip de preproducción de Sporta.
#
# Copia servidor/ a una carpeta temporal renombrada "sporta", aplica los
# ajustes que varían según el entorno de preproducción y empaqueta todo
# en sporta-<VERSION>.zip en la raíz del repo.
#
# NO modifica el working tree del repo: trabaja siempre sobre una copia en
# /tmp/opencode. El zip final queda en la raíz del repo.
#
# Uso:
#   ./hacer-release.sh <VERSION> [--base-url=/sporta] [--sin-recuperar|--con-recuperar]
#
# Opciones:
#   --base-url=URL     Prefijo de URL de la app (default: /sporta)
#   --sin-recuperar    Desactiva la recuperación de contraseña (default: ON)
#   --con-recuperar    Mantiene activa la recuperación (anula --sin-recuperar)
#
# Ejemplos:
#   ./hacer-release.sh 0.9.2
#   ./hacer-release.sh 0.9.2 --base-url=/
#   ./hacer-release.sh 0.9.2 --con-recuperar
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Configuración
# ---------------------------------------------------------------------------
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="$REPO_DIR/servidor"
BUILD_DIR="/tmp/opencode/sporta"
OUT_FILE="$REPO_DIR/sporta-<VERSION>.zip"

# Valores por defecto
BASE_URL="/sporta"
SIN_RECUPERAR=1

# ---------------------------------------------------------------------------
# Argumentos
# ---------------------------------------------------------------------------
VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  echo "Uso: $0 <VERSION> [--base-url=/sporta] [--sin-recuperar|--con-recuperar]" >&2
  exit 1
fi
shift

for arg in "$@"; do
  case "$arg" in
    --base-url=*) BASE_URL="${arg#*=}" ;;
    --sin-recuperar) SIN_RECUPERAR=1 ;;
    --con-recuperar) SIN_RECUPERAR=0 ;;
    *) echo "Opción desconocida: $arg" >&2; exit 1 ;;
  esac
done

if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Error: la versión debe tener formato X.Y.Z (ej: 0.9.2)" >&2
  exit 1
fi

if [[ ! -d "$SOURCE_DIR" ]]; then
  echo "Error: no existe $SOURCE_DIR" >&2
  exit 1
fi

OUT_FILE="${OUT_FILE//<VERSION>/$VERSION}"

# ---------------------------------------------------------------------------
# Limpiar y copiar a temporales
# ---------------------------------------------------------------------------
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"
cp -r "$SOURCE_DIR/." "$BUILD_DIR/"

# ---------------------------------------------------------------------------
# Aplicar ajustes de preproducción sobre la copia
# ---------------------------------------------------------------------------

# 1) BASE_URL en config/init.php
if [[ "$BASE_URL" != "" ]]; then
  sed -i "s|^define('BASE_URL', '.*');|define('BASE_URL', '$BASE_URL');|" "$BUILD_DIR/config/init.php"
  echo "BASE_URL -> $BASE_URL"
else
  sed -i "s|^define('BASE_URL', '.*');|define('BASE_URL', '');|" "$BUILD_DIR/config/init.php"
  echo "BASE_URL -> '' (raíz)"
fi

# 2) Recuperación de contraseña: quitar botón/modal del login y neutralizar endpoint
if [[ "$SIN_RECUPERAR" == "1" ]]; then
  # Quitar botón "¿Olvidaste la contraseña?" del login
  sed -i '/btnCambiarPass/d' "$BUILD_DIR/index.php"
  # Quitar el bloque HTML del modal de recuperación (entre </form> y <script>)
  perl -0pi -e 's/\s*<div id="modalPass"[\s\S]*?<\/div>\s*\n\s*<\/div>(\s*<script>)/$1/s' "$BUILD_DIR/index.php"
  # Quitar el bloque JS que maneja el modal (de 'var modal' hasta antes de </script>)
  perl -0pi -e 's/\n\s*var modal = document\.getElementById\("modalPass"\);[\s\S]*?(?=\n\s*<\/script>)/\n/s' "$BUILD_DIR/index.php"
  # Reemplazar api/recuperar.php por un stub inofensivo
  cat > "$BUILD_DIR/api/recuperar.php" <<'PHP'
<?php

require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json; charset=utf-8');

// Recuperación de contraseña desactivada en esta versión de preproducción.
echo json_encode(['ok' => false, 'mensaje' => 'La recuperación de contraseña no está disponible.']);
PHP
  echo "Recuperación de contraseña: DESACTIVADA"
else
  echo "Recuperación de contraseña: ACTIVA"
fi

# ---------------------------------------------------------------------------
# Empaquetar excluyendo artefactos
# ---------------------------------------------------------------------------
EXCLUDES=()
EXCLUDES+=("-x" "sporta/.git/*")
EXCLUDES+=("-x" "sporta/.env*")
EXCLUDES+=("-x" "sporta/*.log")
EXCLUDES+=("-x" "sporta/index(legacy-NotWorking).html")
# auth/ está vacía (para reemplazos futuros); se excluye para no incluir carpetas vacías
EXCLUDES+=("-x" "sporta/auth/*")

# Eliminar carpetas vacías de la copia antes de zipear (auth/)
find "$BUILD_DIR" -type d -empty -delete 2>/dev/null || true

rm -f "$OUT_FILE"
echo "Empaquetando $OUT_FILE ..."
(cd /tmp/opencode && zip -r -q "$OUT_FILE" sporta "${EXCLUDES[@]}")

# ---------------------------------------------------------------------------
# Verificación
# ---------------------------------------------------------------------------
# Nota: usamos command substitution + here-string en vez de tuberías con
# `grep -q` porque pipefail + SIGPIPE (grep corta la tubería al matchear)
# devolvería 141 aunque el patrón sí coincida.
ZIPLIST="$(unzip -l "$OUT_FILE")"
INIT_PHP="$(unzip -p "$OUT_FILE" sporta/config/init.php)"
LOGIN_PHP="$(unzip -p "$OUT_FILE" sporta/index.php)"

echo "--- Verificación ---"

if ! grep -q "sporta/config/init.php" <<< "$ZIPLIST"; then
  echo "ERROR: falta sporta/config/init.php en el zip" >&2
  exit 1
fi

if ! grep -q "define('BASE_URL', '$BASE_URL')" <<< "$INIT_PHP"; then
  echo "ERROR: BASE_URL incorrecto en el zip" >&2
  exit 1
fi

if grep -qE "\.env|/\.git|legacy" <<< "$ZIPLIST"; then
  echo "ERROR: el zip contiene archivos excluidos (.env/.git/legacy)" >&2
  exit 1
fi

if [[ "$SIN_RECUPERAR" == "1" ]] && grep -q "btnCambiarPass" <<< "$ZIPLIST"; then
  echo "ERROR: el botón de recuperación sigue en el zip" >&2
  exit 1
fi

if [[ "$SIN_RECUPERAR" == "1" ]] && grep -qE "modalPass|var modal|recoverForm" <<< "$LOGIN_PHP"; then
  echo "ERROR: quedó código huérfano de recuperación en index.php del zip" >&2
  exit 1
fi

ls -la "$OUT_FILE"
echo "OK: $OUT_FILE generado correctamente."
echo "Limpieza de /tmp/opencode ..."
rm -rf /tmp/opencode/sporta
