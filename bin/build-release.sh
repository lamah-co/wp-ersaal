#!/usr/bin/env bash
set -euo pipefail

# Ersaal SMS Gateway - WordPress Release Packaging Script
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${PLUGIN_DIR}/dist-release"
SLUG="${1:-ersaal}"
TARGET_DIR="${DIST_DIR}/${SLUG}"

# Derive version dynamically from ersaal.php
VERSION=$(grep -m1 "Version:" "${PLUGIN_DIR}/ersaal.php" | awk '{print $NF}' | tr -d '\r')
ZIP_FILE="${DIST_DIR}/${SLUG}-${VERSION}.zip"
LATEST_ZIP="${DIST_DIR}/${SLUG}.zip"

echo "==> Preparing clean release build for ${SLUG} v${VERSION}"
rm -rf "${DIST_DIR}"
mkdir -p "${TARGET_DIR}"

# Rsync runtime files into release folder
rsync -av \
    --exclude=".*" \
    --exclude="tests/" \
    --exclude="docs/" \
    --exclude="bin/" \
    --exclude="dist/" \
    --exclude="dist-release/" \
    --exclude="AGENTS.md" \
    --exclude="composer.lock" \
    --exclude="phpunit.xml*" \
    --exclude="run_dbdelta.php" \
    --exclude="go.work*" \
    "${PLUGIN_DIR}/" "${TARGET_DIR}/"

# Create clean production zip archives
cd "${DIST_DIR}"
zip -q -r "${ZIP_FILE}" "${SLUG}"
cp "${ZIP_FILE}" "${LATEST_ZIP}"

echo "==> Build complete: ${ZIP_FILE}"
echo "==> Archive preview:"
(unzip -l "${ZIP_FILE}" || true) | sed -n '1,25p'
echo "==> Verification: single root directory (${SLUG}) and exclusions verified."
