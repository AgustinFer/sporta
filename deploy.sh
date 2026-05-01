#!/bin/bash
set -e

# Este archivo debe estar ubicado en /usr/local/bin/deploy.sh

LOG_FILE="/var/log/deploy.log"
REPO_DIR="/var/www/repo/servidor"
WEB_DIR="/var/www/html"
BACKUP_DIR="/var/www/backups"
BRANCH="main"
LOCK_FILE="/tmp/deploy.lock"
LAST_DEPLOY_FILE="/var/www/.last_deploy_commit"

# Lock para evitar ejecuciones simultáneas
if [ -f "$LOCK_FILE" ]; then
  exit 0
fi

trap 'rm -f "$LOCK_FILE"' EXIT
touch "$LOCK_FILE"

cd "$REPO_DIR"

# Obtener estado actual
CURRENT=$(git rev-parse HEAD)

LAST_DEPLOYED=""
if [ -f "$LAST_DEPLOY_FILE" ]; then
  LAST_DEPLOYED=$(cat "$LAST_DEPLOY_FILE")
fi

# Si no cambió el commit, salir silenciosamente
if [ "$CURRENT" = "$LAST_DEPLOYED" ]; then
  exit 0
fi

# A partir de acá → HAY DEPLOY
exec >> "$LOG_FILE" 2>&1

echo "=============================="
echo "Deploy iniciado: $(date)"
echo "=============================="

echo "Commit detectado: $CURRENT"

# Asegurar consistencia con remoto
echo "Sincronizando con remoto..."
git fetch origin >/dev/null 2>&1
git reset --hard origin/$BRANCH

echo "Commit actual:"
git log -1 --oneline

# Backup
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR"

echo "Creando backup..."
cp -r "$WEB_DIR" "$BACKUP_DIR/html_$TIMESTAMP"

# Mantener solo últimos 5 backups
echo "Limpiando backups antiguos..."
ls -dt $BACKUP_DIR/html_* 2>/dev/null | tail -n +6 | xargs -r rm -rf

# Deploy
echo "Sincronizando archivos..."
rsync -r --delete \
  --exclude '.git' \
  "$REPO_DIR/" "$WEB_DIR/"

# Guardar estado
echo "$CURRENT" > "$LAST_DEPLOY_FILE"

echo "Deploy finalizado OK: $(date)"
echo ""
