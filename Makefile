.PHONY: up down build restart logs shell artisan migrate migrate-fresh seed tinker queue-work composer npm

# ── Docker ────────────────────────────────────────────────────────────────────

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart

logs:
	docker compose logs -f

# ── App ───────────────────────────────────────────────────────────────────────

shell:
	docker compose exec app bash

artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

# ── Database ──────────────────────────────────────────────────────────────────

migrate:
	docker compose exec app php artisan migrate

migrate-fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

# ── Development ──────────────────────────────────────────────────────────────

tinker:
	docker compose exec app php artisan tinker

queue-work:
	docker compose exec app php artisan queue:work --tries=3

# ── Dependencies ─────────────────────────────────────────────────────────────

composer:
	docker compose exec app composer $(filter-out $@,$(MAKECMDGOALS))

npm:
	docker compose exec app npm $(filter-out $@,$(MAKECMDGOALS))

# ── Setup (first run) ────────────────────────────────────────────────────────

setup:
	cp -n .env.example .env || true
	docker compose up -d --build
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate
	docker compose exec app php artisan storage:link
	@echo "\n✅  Setup complete! Visit http://localhost"

# ── Utilities ────────────────────────────────────────────────────────────────

clear:
	docker compose exec app php artisan optimize:clear

optimize:
	docker compose exec app php artisan optimize

ide-helper:
	docker compose exec app php artisan ide-helper:generate
	docker compose exec app php artisan ide-helper:models -M
	docker compose exec app php artisan ide-helper:meta

# Catch-all to prevent "No rule to make target" errors when passing extra args
%:
	@:
