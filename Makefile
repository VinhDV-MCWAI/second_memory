# Second Memory — task runner. Run `make help` for the list of targets.
# All tooling runs inside the Docker containers; the host only needs Docker + make.

SHELL := /usr/bin/env bash
.DEFAULT_GOAL := help

COMPOSE := docker compose -f docker/docker-compose.yml
PHP     := docker exec ml-php
FE      := docker exec ml-nextjs
DOCS    := docker exec ml-nextjs-docs
IMPORTER_DIR := tools/ledger-importer

##@ Environment

.PHONY: setup
setup: ## Generate docker/.env and app env files (idempotent)
	bash setup-env.sh

.PHONY: up
up: ## Start the whole stack (generates env on first run)
	@[ -f docker/.env ] || $(MAKE) setup
	$(COMPOSE) up -d --build --renew-anon-volumes

.PHONY: down
down: ## Stop the stack (keeps volumes)
	$(COMPOSE) down

.PHONY: restart
restart: ## Recreate containers and rebuild images (picks up env changes)
	$(COMPOSE) up -d --build --force-recreate

.PHONY: ps
ps: ## Show container status
	$(COMPOSE) ps

.PHONY: logs
logs: ## Follow logs (make logs s=ml-php)
	$(COMPOSE) logs -f --tail=200 $(s)

.PHONY: sh
sh: ## Shell into a container (make sh s=ml-php)
	docker exec -it $(or $(s),ml-php) sh

##@ Backend (laravel-api)

.PHONY: test
test: ## Run backend tests (make test f=AdminMst to filter)
	$(PHP) php artisan test $(if $(f),--filter=$(f),)

.PHONY: test-ci
test-ci: ## Run backend tests like CI
	$(PHP) php artisan test

.PHONY: pint
pint: ## Format PHP code
	$(PHP) composer format

.PHONY: analyse
analyse: ## Static analysis (Larastan)
	$(PHP) composer analyse

.PHONY: openapi
openapi: ## Regenerate laravel-api/openapi.json and the admin FE types (commit both; CI checks them)
	$(PHP) php artisan scramble:export
	@# The FE container only mounts nextjs-fe; copy the spec to where `gen:api` looks for it
	$(FE) mkdir -p /repo/laravel-api
	docker cp laravel-api/openapi.json ml-nextjs:/repo/laravel-api/openapi.json
	$(FE) sh -c 'pnpm gen:api && chown $(shell id -u):$(shell id -g) src/shared/types/openapi.d.ts'

.PHONY: rector
rector: ## Preview automated refactors (dry run)
	$(PHP) composer rector

.PHONY: migrate
migrate: ## Run pending migrations on the dev database
	$(PHP) php artisan migrate

.PHONY: fresh
fresh: ## DROP all dev tables, re-migrate and seed (asks first)
	@read -r -p "This wipes the dev database. Type 'yes' to continue: " ans; [ "$$ans" = yes ]
	$(PHP) php artisan migrate:fresh --seed --force

##@ Frontend

.PHONY: fe-lint
fe-lint: ## Lint both Next.js apps
	$(FE) pnpm lint
	$(DOCS) pnpm lint

.PHONY: fe-typecheck
fe-typecheck: ## Type-check both Next.js apps
	$(FE) pnpm typecheck
	$(DOCS) pnpm typecheck

.PHONY: fe-test
fe-test: ## Run admin FE unit tests
	$(FE) pnpm test --run --coverage

.PHONY: fe-format
fe-format: ## Format both Next.js apps (Prettier)
	$(FE) pnpm format
	$(DOCS) pnpm format

##@ Quality gates

.PHONY: format
format: pint fe-format ## Format everything (Pint + Prettier)

.PHONY: lint
lint: ## All linters/format checks (backend + frontend)
	$(PHP) composer lint
	$(MAKE) fe-lint
	$(FE) pnpm format:check
	$(DOCS) pnpm format:check
	$(MAKE) importer-lint

.PHONY: openapi-check
openapi-check: openapi ## Fail if openapi.json or the FE types were stale (CI is paused, so make verify runs this)
	git diff --exit-code -- laravel-api/openapi.json nextjs-fe/src/shared/types/openapi.d.ts || { echo "OpenAPI spec or FE types were stale: review and commit the regenerated files"; exit 1; }

.PHONY: verify
verify: lint analyse openapi-check fe-typecheck test-ci fe-test importer-test ## Everything CI runs, plus the OpenAPI drift check

##@ Ledger importer (tools/ledger-importer, ADR-0010)

.PHONY: importer-dev-image
importer-dev-image:
	docker build -q --target dev -t ledger-importer-dev $(IMPORTER_DIR) >/dev/null

.PHONY: importer-lint
importer-lint: importer-dev-image ## Importer: ruff + mypy --strict
	docker run --rm ledger-importer-dev sh -c 'ruff check && ruff format --check && mypy'

.PHONY: importer-test
importer-test: importer-dev-image ## Importer: pytest (API mocked)
	docker run --rm ledger-importer-dev pytest -p no:cacheprovider

.PHONY: import
import: ## Import published Obsidian notes (make import vault=<path> [dry=1]; needs LEDGER_API_TOKEN)
	@test -d "$(vault)" || { echo "usage: make import vault=<path to the vault> [dry=1]"; exit 2; }
	docker build -q -t ledger-importer $(IMPORTER_DIR) >/dev/null
	docker run --rm --network ml_network -v "$(abspath $(vault)):/vault:ro" \
		-e LEDGER_API_TOKEN -e LEDGER_API_URL -e LEDGER_NOTE_BASE_URL \
		ledger-importer $(if $(dry),--dry-run)

##@ Data

.PHONY: backup
backup: ## Back up PostgreSQL + MinIO
	bash backup/backup.sh

.PHONY: restore
restore: ## Restore from a backup (see backup/README.md)
	bash backup/restore.sh

##@ Help

.PHONY: help
help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage: make \033[36m<target>\033[0m\n"} \
		/^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2 } \
		/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)
