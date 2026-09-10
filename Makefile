LOCALDEV_ENV_FILE ?= .env.localdev
DOCKER_COMPOSE ?= docker compose
COMPOSE = $(DOCKER_COMPOSE) --env-file $(LOCALDEV_ENV_FILE)

.DEFAULT_GOAL := help

.PHONY: help require-env setup up down reset ps db.up php.up nginx.up php.build \
	logs php.log php.log.clear shell php-shell nginx-shell db-shell wp \
	composer.install check lint test test.unit test.integration \
	test.integration.minimum test.integration.latest smoke hosts-check hosts-add hosts-remove

help:
	@printf '%s\n' \
		'make setup  - build and reconcile the complete local WordPress site' \
		'make up     - start db, PHP-FPM, and nginx and wait for health checks' \
		'make down   - stop the stack and preserve local data' \
		'make reset  - explicitly remove this project local data' \
		'make ps     - show this Compose project services' \
		'make php.build - rebuild the local PHP image' \
		'make logs [SERVICE=php] - follow Docker service logs' \
		'make php.log - follow the PHP application error log' \
		'make php.log.clear - truncate PHP application logs' \
		'make shell  - open Bash in the PHP container' \
		'make nginx-shell - open a shell in nginx' \
		'make db-shell - open the MySQL client' \
		'make wp ARGS="post list" - run WP-CLI in PHP' \
		'make composer.install - install Composer dependencies in PHP' \
		'make check  - validate Composer, lint PHP, and run unit tests' \
		'make test   - run PHPUnit in the PHP container' \
		'make test.integration - run clean minimum and latest WordPress profiles' \
		'make smoke  - exercise wpPostAble against the local WordPress database' \
		'make hosts-check|hosts-add|hosts-remove - manage the local hostname'

require-env:
	@test -f "$(LOCALDEV_ENV_FILE)" || { printf 'Missing %s; run make setup first.\n' "$(LOCALDEV_ENV_FILE)" >&2; exit 1; }

setup: hosts-add
	LOCALDEV_ENV_FILE="$(LOCALDEV_ENV_FILE)" ./scripts/setup-local.sh

db.up: require-env
	$(COMPOSE) up -d --wait db

php.up: require-env
	$(COMPOSE) up -d --wait db php

nginx.up: require-env
	$(COMPOSE) up -d --wait db php nginx

up: nginx.up

down: require-env
	$(COMPOSE) down

reset:
	LOCALDEV_ENV_FILE="$(LOCALDEV_ENV_FILE)" ./scripts/reset-local.sh

ps: require-env
	$(COMPOSE) ps

php.build: require-env
	$(COMPOSE) build --pull php

logs: require-env
	$(COMPOSE) up -d db php nginx
	$(COMPOSE) logs --follow --tail=100 --timestamps $(SERVICE)

php.log: php.up
	$(COMPOSE) exec -T php sh -lc 'touch /var/log/php/error.log && tail -n 50 -F /var/log/php/error.log'

php.log.clear: php.up
	$(COMPOSE) exec -T php sh -lc 'for log_file in /var/log/php/*.log; do [ -e "$${log_file}" ] || continue; : > "$${log_file}"; done'

shell: php-shell

php-shell: php.up
	$(COMPOSE) exec php bash

nginx-shell: nginx.up
	$(COMPOSE) exec nginx sh

db-shell: db.up
	$(COMPOSE) exec db sh -lc 'MYSQL_PWD="$${MYSQL_ROOT_PASSWORD}" mysql --user=root "$${MYSQL_DATABASE}"'

wp: php.up
	$(COMPOSE) exec -T php wp $(ARGS)

composer.install: php.up
	$(COMPOSE) exec -T --workdir /workspace php composer install --no-interaction

check: composer.install
	$(COMPOSE) exec -T --workdir /workspace php composer check

lint: composer.install
	$(COMPOSE) exec -T --workdir /workspace php composer lint

test: test.unit

test.unit: composer.install
	$(COMPOSE) exec -T --workdir /workspace php composer test:test-unit

test.integration: composer.install
	./scripts/test-integration.sh all

test.integration.minimum: composer.install
	./scripts/test-integration.sh minimum

test.integration.latest: composer.install
	./scripts/test-integration.sh latest

smoke: php.up
	$(COMPOSE) exec -T php wp wppostable smoke

hosts-check:
	LOCALDEV_ENV_FILE="$(LOCALDEV_ENV_FILE)" ./scripts/local-domain.sh check

hosts-add:
	LOCALDEV_ENV_FILE="$(LOCALDEV_ENV_FILE)" ./scripts/local-domain.sh add

hosts-remove:
	LOCALDEV_ENV_FILE="$(LOCALDEV_ENV_FILE)" ./scripts/local-domain.sh remove
