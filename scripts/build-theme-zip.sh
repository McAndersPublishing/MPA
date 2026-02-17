#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME_DIR="$ROOT_DIR/wordpress/wp-content/themes/mpa-books-2026"
DIST_DIR="$ROOT_DIR/dist"
ZIP_PATH="$DIST_DIR/mpa-books-2026-theme.zip"

if [[ ! -d "$THEME_DIR" ]]; then
  echo "Theme directory not found: $THEME_DIR" >&2
  exit 1
fi

mkdir -p "$DIST_DIR"
rm -f "$ZIP_PATH"

(
  cd "$ROOT_DIR/wordpress/wp-content/themes"
  zip -r "$ZIP_PATH" "mpa-books-2026" \
    -x "*/.DS_Store" "*/__MACOSX/*" "*/.git/*" "*/node_modules/*"
)

echo "Created: $ZIP_PATH"
