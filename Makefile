.PHONY: up setup seed down reset sync zip lint check
up:
	npm install
	docker compose up -d
	./scripts/setup.sh
seed:
	./scripts/seed-content.sh --force
sync:
	npm run sync
down:
	docker compose down
reset:
	docker compose down -v
zip:
	./scripts/build-zip.sh
lint:
	vendor/bin/phpcs
check: lint
	docker compose run --rm -T wpcli wp eval-file /php/check-events.php
	npm run --silent check:editor
	npm run --silent check:customizer
	npm run --silent check:ics
	npm run --silent check:frontend
	npm run --silent check:a11y
