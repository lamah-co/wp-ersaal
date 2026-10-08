#!/usr/bin/env bash
set -euo pipefail

# Ersaal SMS Gateway - WordPress.org Release Packaging Script
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${PLUGIN_DIR}/dist"
PACKAGE_DIR="wp-ersaal"
VERSION="$(sed -n 's/^ \* Version: //p' "${PLUGIN_DIR}/ersaal.php" | head -n 1)"
if [[ -z "${VERSION}" ]]; then
    echo "Unable to read the plugin version from ersaal.php" >&2
    exit 1
fi
TARGET_DIR="${DIST_DIR}/${PACKAGE_DIR}"
ZIP_FILE="${DIST_DIR}/${PACKAGE_DIR}.zip"
VERSIONED_ZIP="${DIST_DIR}/${PACKAGE_DIR}-v${VERSION}.zip"

echo "==> Preparing clean release build for WordPress: ${PACKAGE_DIR} v${VERSION}"
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
    --exclude="composer.json" \
    --exclude="composer.lock" \
    --exclude="phpunit.xml*" \
    --exclude="run_dbdelta.php" \
    --exclude="go.work*" \
    "${PLUGIN_DIR}/" "${TARGET_DIR}/"

# Create clean production zip
cd "${DIST_DIR}"
zip -q -r "${ZIP_FILE}" "${PACKAGE_DIR}"
cp "${ZIP_FILE}" "${VERSIONED_ZIP}"

echo "==> Build complete:"
echo "    - ${ZIP_FILE}"
echo "    - ${VERSIONED_ZIP}"
echo ""
echo "==> Zip file contents (top 20 entries):"
unzip -l "${ZIP_FILE}" | sed -n '1,25p'
