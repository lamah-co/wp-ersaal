#!/usr/bin/env bash
set -euo pipefail

# Ersaal SMS Gateway - WordPress Release Packaging Script
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${PLUGIN_DIR}/dist-release"
SLUG="${1:-wp-ersaal}"
if [[ ! "${SLUG}" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]]; then
    echo "Invalid plugin folder name: ${SLUG}" >&2
    exit 1
fi
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
    --exclude="/assets/" \
    --exclude="*.md" \
    --exclude="tests/" \
    --exclude="docs/" \
    --exclude="bin/" \
    --exclude="dist/" \
    --exclude="dist-release/" \
    --exclude="composer.lock" \
    --exclude="composer.json" \
    --exclude="*~" \
    --exclude="phpunit.xml*" \
    --exclude="run_dbdelta.php" \
    --exclude="go.work*" \
    "${PLUGIN_DIR}/" "${TARGET_DIR}/"

# Fail before publishing an incomplete package.
for required in ersaal.php readme.txt vendor/autoload.php \
    languages/ersaal-sms-gateway.pot \
    languages/ersaal-sms-gateway-ar.po \
    languages/ersaal-sms-gateway-ar.mo; do
    test -s "${TARGET_DIR}/${required}" || { echo "Missing release file: ${required}" >&2; exit 1; }
done
while IFS= read -r -d '' asset; do
    cmp "${asset}" "${TARGET_DIR}/${asset#"${PLUGIN_DIR}/"}"
done < <(find "${PLUGIN_DIR}/admin/assets" -type f -print0)

# Create clean production zip archives
cd "${DIST_DIR}"
zip -q -r "${ZIP_FILE}" "${SLUG}"
cp "${ZIP_FILE}" "${LATEST_ZIP}"
unzip -tq "${ZIP_FILE}"

echo "==> Build complete: ${ZIP_FILE}"
echo "==> Archive preview:"
(unzip -l "${ZIP_FILE}" || true) | sed -n '1,25p'
echo "==> Required release files and admin assets verified."
