#!/usr/bin/env bash
# Installs WordPress in the running container and activates the basix theme.
set -euo pipefail
cd "$(dirname "$0")/.."

wp() { docker compose run --rm wpcli wp "$@"; }

until wp db check >/dev/null 2>&1; do echo "waiting for database..."; sleep 3; done

if ! wp core is-installed 2>/dev/null; then
  wp core install --url=http://localhost:8090 --title="Basix" \
    --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
fi
wp theme activate basix
if [ -z "$(wp widget list sidebar-1 --format=ids)" ]; then
  wp widget add search sidebar-1
  wp widget add recent-posts sidebar-1 --number=5
  wp widget add categories sidebar-1
fi
wp rewrite structure '/%postname%/' --hard
echo "Ready: http://localhost:8090  (admin / admin)"
