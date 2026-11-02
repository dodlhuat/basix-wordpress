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
	./scripts/check.sh
