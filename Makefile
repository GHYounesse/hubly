.DEFAULT_GOAL := help
.PHONY: help install jwt key worker db fixtures demo test lint cs stan css watch serve

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

install: ## Install PHP and Node dependencies
	composer install
	npm install

jwt: ## Generate the JWT key pair of the REST API (once)
	php bin/console lexik:jwt:generate-keypair --skip-if-exists

key: ## Print a new APP_ENCRYPTION_KEY to put in .env
	php bin/console app:encryption:generate-key

worker: ## Run the scheduler worker (URL, SSL and hosting checks, daily digest)
	php bin/console messenger:consume scheduler_default -vv

db: ## Create the database and run the migrations
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction

fixtures: ## Load the demo data (users, clients, projects, uptime history)
	php bin/console doctrine:fixtures:load --group=demo --no-interaction

demo: ## Start the whole demo with Docker (app + PostgreSQL + demo data)
	docker compose -f docker-compose.demo.yml up --build

test: ## Run the PHPUnit test suite
	vendor/bin/phpunit

lint: ## Lint YAML, Twig, the container and the Doctrine schema
	php bin/console lint:yaml config --parse-tags
	php bin/console lint:twig templates
	php bin/console lint:container
	php bin/console doctrine:schema:validate

cs: ## Check the code style (php-cs-fixer, dry run)
	vendor/bin/php-cs-fixer fix --dry-run --diff

stan: ## Run static analysis (PHPStan)
	vendor/bin/phpstan analyse --memory-limit=512M

css: ## Build the Tailwind CSS once
	npm run build

watch: ## Rebuild the Tailwind CSS on change
	npm run dev

serve: ## Start the local web server
	symfony serve
