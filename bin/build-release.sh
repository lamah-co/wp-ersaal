#!/usr/bin/env bash
set -euo pipefail

# Ersaal SMS Gateway - WordPress.org Release Packaging Script
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${PLUGIN_DIR}/../../../../dist-release"
SLUG="ersaal"
TARGET_DIR="${DIST_DIR}/${SLUG}"
ZIP_FILE="${DIST_DIR}/${SLUG}.zip"

echo "==> Preparing clean release build for WordPress.org: ${SLUG}"
rm -rf "${DIST_DIR}"
mkdir -p "${TARGET_DIR}"

# Rsync runtime files into dist/ersaal
rsync -av \
    --exclude=".*" \
    --exclude="tests/" \
    --exclude="docs/" \
    --exclude="bin/" \
    --exclude="dist/" \
    --exclude="AGENTS.md" \
    --exclude="composer.lock" \
    --exclude="phpunit.xml*" \
    --exclude="run_dbdelta.php" \
    --exclude="go.work*" \
    "${PLUGIN_DIR}/" "${TARGET_DIR}/"

# Create clean production zip
cd "${DIST_DIR}"
zip -q -r "${ZIP_FILE}" "${SLUG}"
echo "==> Build complete: ${ZIP_FILE}"
echo "==> Contents:"
unzip -l "${ZIP_FILE}" | head -25
