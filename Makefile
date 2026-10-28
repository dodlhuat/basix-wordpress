.PHONY: up setup seed down reset sync
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
