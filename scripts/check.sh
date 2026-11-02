#!/usr/bin/env bash
# Runs every check against the local site in English, whatever language the site is set to, and puts the language
# back afterwards (also when a check fails or the run is interrupted). The checks look for English texts.
set -uo pipefail
cd "$(dirname "$0")/.."

wp() { docker compose run --rm -T wpcli wp "$@"; }

language=$(wp option get WPLANG 2>/dev/null | tail -n 1 | tr -d '\r')
restore() { wp option update WPLANG "$language" >/dev/null 2>&1; }
trap restore EXIT
trap 'exit 130' INT TERM
wp option update WPLANG '' >/dev/null

status=0
run() {
  [ "$status" -eq 0 ] || return 0
  "$@" || status=$?
}
run wp eval-file /php/check-events.php
run wp eval-file /php/check-looks.php
run wp eval-file /php/check-starters.php
run wp eval-file /php/check-header.php
run wp eval-file /php/check-compat.php
run npm run --silent check:editor
run npm run --silent check:customizer
run npm run --silent check:ics
run npm run --silent check:schema
run npm run --silent check:looks
run npm run --silent check:accent
run npm run --silent check:starters
run npm run --silent check:header
run npm run --silent check:compat
run npm run --silent check:rtl
run npm run --silent check:frontend
run npm run --silent check:a11y
run npm run --silent check:no-plugin
exit "$status"
