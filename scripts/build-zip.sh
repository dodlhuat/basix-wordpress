#!/usr/bin/env bash
# Builds an installable theme zip in dist/: syncs Basix, then packages only the theme folder.
set -euo pipefail
cd "$(dirname "$0")/.."

slug="basix"
version=$(sed -n 's/^Version: *//p' theme/style.css | head -1)
[ -n "$version" ] || { echo "No Version found in theme/style.css" >&2; exit 1; }

npm run --silent sync

out="dist/${slug}-${version}.zip"
stage="dist/stage"
rm -rf dist
mkdir -p "$stage"
cp -R theme "$stage/$slug"

# Keep the package clean: no OS files, source maps or TypeScript declarations.
find "$stage" \( -name '.DS_Store' -o -name '*.map' -o -name '*.d.ts' \) -delete

(cd "$stage" && zip -qr "../${slug}-${version}.zip" "$slug")
rm -rf "$stage"

echo "Built $out ($(du -h "$out" | cut -f1))"
