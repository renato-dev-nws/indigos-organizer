ENV_FILE ?= .env.production

ifneq (,$(wildcard $(ENV_FILE)))
include $(ENV_FILE)
export
endif

SERVER ?=
REMOTE_DIR ?= ~/indigos-organizer
COMPOSE_FILE ?= docker-compose-production.yml
APP_SERVICE ?= indigos-organizer

RSYNC_EXCLUDES = \
	--exclude .git/ \
	--exclude node_modules/ \
	--exclude vendor/ \
	--exclude storage/logs/ \
	--exclude storage/framework/cache/* \
	--exclude storage/framework/sessions/* \
	--exclude storage/framework/views/*

.PHONY: check-env rsync prepare-env deploy composer-install up down restart logs shell fix-permissions \
	artisan optimize migrate seed-all seed-demo-all settings-seed

check-env:
	@test -n "$(SERVER)" || (echo "ERRO: defina SERVER no $(ENV_FILE)" && exit 1)

rsync: check-env
	rsync -az --delete $(RSYNC_EXCLUDES) ./ $(SERVER):$(REMOTE_DIR)/

prepare-env: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && cp -f .env.production .env"

deploy: rsync prepare-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) down --remove-orphans && docker compose -f $(COMPOSE_FILE) up -d --build"
	$(MAKE) composer-install
	$(MAKE) fix-permissions
	$(MAKE) optimize

composer-install: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) composer install --no-dev --no-interaction --optimize-autoloader"

up: check-env prepare-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) up -d"

down: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) down"

restart: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) restart"

logs: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) logs -f"

shell: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec $(APP_SERVICE) sh"

fix-permissions: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) sh -lc 'chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache'"

artisan: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) php artisan $(CMD)"

optimize: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) php artisan optimize"

migrate: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) php artisan migrate --force"

seed-all: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) php artisan db:seed --force"

seed-demo-all: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) php artisan migrate:fresh --seed --force"

settings-seed: check-env
	ssh $(SERVER) "cd $(REMOTE_DIR) && docker compose -f $(COMPOSE_FILE) exec -T $(APP_SERVICE) php artisan settings:seed --no-interaction"
