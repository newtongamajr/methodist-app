#!/usr/bin/env bash
# Media-pipeline system dependencies for Methodist App (one-shot, idempotent).
#
# Spatie media-library's PDF and Video image generators rely on system
# binaries + a PHP extension. The composer packages (spatie/pdf-to-image,
# php-ffmpeg/php-ffmpeg) are pinned in composer.json and installed via
# `composer install` — this script handles only what needs sudo.
#
# What it does:
#   1. apt-get install: php8.4-imagick + imagemagick + ghostscript + ffmpeg
#   2. Patches /etc/ImageMagick-6/policy.xml so PDFs can be read (the
#      stock policy denies "PDF" coder for security; we need it for
#      first-page rendering).
#   3. Reloads php8.4-fpm so the imagick extension is picked up.
#
# Usage:
#   sudo bash scripts/install-media-deps.sh
#
# Re-running is safe: apt-get is a no-op when packages are current, the
# sed is a guard-conditioned replace, and the php-fpm reload is best-
# effort. After this finishes, run from the project root:
#
#   php artisan media-library:regenerate
#
# …to backfill thumb conversions for any media already in the DB.

set -euo pipefail

PHP_VERSION="8.4"
IMAGICK_POLICY="/etc/ImageMagick-6/policy.xml"

PACKAGES=(
    "php${PHP_VERSION}-imagick"
    "imagemagick"
    "ghostscript"
    "ffmpeg"
)

# ---- Guard --------------------------------------------------------------- #

if [[ $EUID -ne 0 ]]; then
    echo "[!] This script must be run with sudo (it edits /etc/ImageMagick-*/policy.xml" >&2
    echo "    and runs apt-get / systemctl)." >&2
    exit 1
fi

# ---- 1. Install packages ------------------------------------------------- #

echo "==> Installing media-pipeline packages: ${PACKAGES[*]}"
DEBIAN_FRONTEND=noninteractive apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends "${PACKAGES[@]}"

# ---- 2. ImageMagick PDF policy ------------------------------------------ #
#
# The stock policy file blocks the PDF coder for CVE-2018-16323 / Ghostscript
# safety reasons. We need it open so Spatie's Pdf generator can rasterize
# the first page. Patch is a one-line replace, only applied if the deny
# is still present (so re-runs are no-ops).

if [[ -f "$IMAGICK_POLICY" ]]; then
    echo "==> Patching ImageMagick PDF policy at $IMAGICK_POLICY"
    if grep -q 'rights="none" pattern="PDF"' "$IMAGICK_POLICY"; then
        # Backup once
        if [[ ! -f "${IMAGICK_POLICY}.bak.methodist" ]]; then
            cp "$IMAGICK_POLICY" "${IMAGICK_POLICY}.bak.methodist"
        fi
        sed -i 's|<policy domain="coder" rights="none" pattern="PDF" />|<policy domain="coder" rights="read\|write" pattern="PDF" />|' "$IMAGICK_POLICY"
        echo "    patched (backup at ${IMAGICK_POLICY}.bak.methodist)"
    else
        echo "    already patched — no change"
    fi
else
    echo "    skip: $IMAGICK_POLICY not found (ImageMagick 7? edit /etc/ImageMagick-7/policy.xml manually if so)"
fi

# ---- 3. Reload php-fpm so imagick loads --------------------------------- #

echo "==> Reloading php${PHP_VERSION}-fpm so the imagick extension is picked up"
if systemctl list-unit-files "php${PHP_VERSION}-fpm.service" --no-legend 2>/dev/null | grep -q .; then
    systemctl restart "php${PHP_VERSION}-fpm" || echo "    php-fpm restart failed (ignored — service may not be in use here)"
else
    echo "    php${PHP_VERSION}-fpm service not registered — skip (you're probably running PHP via CLI only)"
fi

# ---- Verification --------------------------------------------------------- #

echo
echo "==> Verifying installation"
if php"${PHP_VERSION}" -m | grep -qi '^imagick$'; then
    echo "    imagick: OK"
else
    echo "    imagick: MISSING (check apt output above)"
fi
for bin in convert gs ffmpeg ffprobe; do
    if command -v "$bin" >/dev/null 2>&1; then
        echo "    $bin: $(command -v "$bin")"
    else
        echo "    $bin: MISSING"
    fi
done

echo
echo "Done. Next:"
echo "  cd /path/to/methodist-app"
echo "  php artisan media-library:regenerate"
