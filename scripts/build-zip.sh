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

# The companion plugin, as its own zip.
plugin_version=$(sed -n 's/^ \* Version: *//p' basix-events/basix-events.php | head -1)
[ -n "$plugin_version" ] || { echo "No Version found in basix-events/basix-events.php" >&2; exit 1; }
plugin_out="dist/basix-events-${plugin_version}.zip"
mkdir -p "$stage"
cp -R basix-events "$stage/basix-events"
find "$stage" \( -name '.DS_Store' -o -name '*.map' -o -name '*.pot' \) -delete
(cd "$stage" && zip -qr "../basix-events-${plugin_version}.zip" basix-events)
rm -rf "$stage"
echo "Built $plugin_out ($(du -h "$plugin_out" | cut -f1))"
