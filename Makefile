.PHONY: up setup down reset sync
up:
	npm install
	docker compose up -d
	./scripts/setup.sh
sync:
	npm run sync
down:
	docker compose down
reset:
	docker compose down -v
