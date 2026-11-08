# ─────────────────────────────────────────────────────────────────────────────
# Melody — raccourcis de développement
# ─────────────────────────────────────────────────────────────────────────────
PHP      ?= php
COMPOSER ?= composer
CONSOLE  ?= $(PHP) bin/console

.DEFAULT_GOAL := help
.PHONY: help install start db migrate fixtures test lint cs docker-up docker-down docker-logs clean

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

install: ## Installe les dépendances PHP
	$(COMPOSER) install

start: ## Démarre le serveur de développement (http://localhost:8000)
	$(PHP) -S 0.0.0.0:8000 -t public

db: ## Crée la base de données
	$(CONSOLE) doctrine:database:create --if-not-exists

migrate: ## Applique les migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

fixtures: ## Charge le jeu de données de démonstration
	$(CONSOLE) doctrine:fixtures:load --no-interaction

test: ## Lance la suite de tests (SQLite)
	$(PHP) bin/phpunit --colors=always

lint: ## Vérifie les templates Twig et les fichiers YAML
	$(CONSOLE) lint:twig templates
	$(CONSOLE) lint:yaml config

docker-up: ## Construit et démarre la pile (http://localhost:8080)
	docker compose up -d --build

docker-down: ## Arrête la pile
	docker compose down

docker-logs: ## Suit les logs du conteneur applicatif
	docker compose logs -f app

clean: ## Purge le cache et les fichiers temporaires
	rm -rf var/cache var/log var/test.db
