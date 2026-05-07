#!/usr/bin/env bash
# Host setup for Jejum & Oração Metodista (one-shot, idempotent).
#
# What it does:
#   1. Bumps PHP 8.4 upload_max_filesize / post_max_size to 100M (cli + fpm).
#   2. Reloads php8.4-fpm (best effort; ignored if not running as a service).
#   3. Fixes storage/ + bootstrap/cache permissions for Docker PHP-FPM (www-data).
#
# Usage:
#   sudo bash /tmp/jejum-oracao-host-setup.sh
#
# Re-running is safe: sed targets specific keys regardless of current value, and
# chown / chmod / setgid are idempotent.

set -euo pipefail

PROJECT_DIR="/home/galileosoft/git/laravel/jejum-oracao-metodista"
PHP_INI_FILES=(
    "/etc/php/8.4/cli/php.ini"
    "/etc/php/8.4/fpm/php.ini"
)
TARGET_UPLOAD="100M"
TARGET_POST="100M"
OWNER_USER="galileosoft"
OWNER_GROUP="www-data"

# ---- Guard --------------------------------------------------------------- #

if [[ $EUID -ne 0 ]]; then
    echo "[!] This script must be run with sudo (it edits /etc/php/* and chowns)." >&2
    exit 1
fi

if [[ ! -d "$PROJECT_DIR" ]]; then
    echo "[!] Project directory not found: $PROJECT_DIR" >&2
    exit 1
fi

# ---- 1. PHP upload limits ----------------------------------------------- #

echo "==> Bumping PHP upload limits to ${TARGET_UPLOAD} / ${TARGET_POST}"
for ini in "${PHP_INI_FILES[@]}"; do
    if [[ ! -f "$ini" ]]; then
        echo "    skip: $ini not found"
        continue
    fi

    # Backup once (don't overwrite on re-runs)
    if [[ ! -f "${ini}.bak.jejum" ]]; then
        cp "$ini" "${ini}.bak.jejum"
        echo "    backup: ${ini}.bak.jejum"
    fi

    sed -i -E "s/^[[:space:]]*upload_max_filesize[[:space:]]*=.*/upload_max_filesize = ${TARGET_UPLOAD}/" "$ini"
    sed -i -E "s/^[[:space:]]*post_max_size[[:space:]]*=.*/post_max_size = ${TARGET_POST}/" "$ini"

    # Verify
    actual_upload=$(grep -E "^[[:space:]]*upload_max_filesize" "$ini" | head -1 | awk -F= '{print $2}' | tr -d ' ')
    actual_post=$(grep -E "^[[:space:]]*post_max_size" "$ini" | head -1 | awk -F= '{print $2}' | tr -d ' ')
    echo "    $ini → upload=${actual_upload} post=${actual_post}"
done

# ---- 2. Reload php8.4-fpm (best effort) ---------------------------------- #

echo "==> Reloading php8.4-fpm (best effort)"
if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files | grep -q '^php8\.4-fpm\.service'; then
    if systemctl reload php8.4-fpm 2>/dev/null; then
        echo "    systemctl reload php8.4-fpm: OK"
    elif systemctl restart php8.4-fpm 2>/dev/null; then
        echo "    systemctl restart php8.4-fpm: OK"
    else
        echo "    [warn] php8.4-fpm not running via systemctl — reload manually if you use it"
    fi
elif command -v service >/dev/null 2>&1 && service php8.4-fpm status >/dev/null 2>&1; then
    service php8.4-fpm reload && echo "    service reload: OK"
else
    echo "    [warn] no php8.4-fpm service detected (WSL?) — reload manually if you use it"
fi

# ---- 3. Storage + bootstrap/cache perms (Docker PHP-FPM) ----------------- #

echo "==> Fixing permissions on storage/ and bootstrap/cache/"
cd "$PROJECT_DIR"

if ! id -u "$OWNER_USER" >/dev/null 2>&1; then
    echo "[!] User $OWNER_USER not found — skipping chown" >&2
elif ! getent group "$OWNER_GROUP" >/dev/null 2>&1; then
    echo "[!] Group $OWNER_GROUP not found — skipping chown" >&2
else
    chown -R "${OWNER_USER}:${OWNER_GROUP}" storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod g+s {} \;
    echo "    owner=${OWNER_USER}:${OWNER_GROUP} mode=775 setgid=on"
fi

echo
echo "==> Done."
echo "    PHP CLI version : $(php8.4 -v | head -1)"
echo "    Verify uploads  : php8.4 -r 'echo ini_get(\"upload_max_filesize\").\" / \".ini_get(\"post_max_size\").PHP_EOL;'"
echo "    Verify storage  : ls -ld storage bootstrap/cache"