# Second Memory — task runner. Run `make help` for the list of targets.
# All tooling runs inside the Docker containers; the host only needs Docker + make.

SHELL := /usr/bin/env bash
.DEFAULT_GOAL := help

COMPOSE := docker compose -f docker/docker-compose.yml
PHP     := docker exec ml-php
FE      := docker exec ml-nextjs
DOCS    := docker exec ml-nextjs-docs

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
restart: ## Recreate containers (picks up env changes)
	$(COMPOSE) up -d --force-recreate

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
test: ## Run backend tests (make test f=CategoryMgmt to filter)
	$(PHP) php artisan test $(if $(f),--filter=$(f),)

.PHONY: pint
pint: ## Format PHP code
	$(PHP) ./vendor/bin/pint

.PHONY: analyse
analyse: ## Static analysis (Larastan)
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit=1G

.PHONY: rector
rector: ## Preview automated refactors (dry run)
	$(PHP) ./vendor/bin/rector process --dry-run

.PHONY: migrate
migrate: ## Run pending migrations on the dev database
	$(PHP) php artisan migrate

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
	$(FE) pnpm test --run

##@ Quality gates

.PHONY: lint
lint: ## All linters/format checks (backend + frontend)
	$(PHP) ./vendor/bin/pint --test
	$(MAKE) fe-lint

.PHONY: verify
verify: lint analyse fe-typecheck test fe-test ## Everything CI runs

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
