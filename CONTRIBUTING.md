# Contributing to Hubly

Thanks for your interest! Here is how to get a change merged.

## Setup

Follow the *Getting started* section of the [README](README.md).

## Workflow

1. Create a branch from `main`: `git checkout -b feat/short-description`
2. Make your change, keeping commits small and focused.
3. Run the checks locally:
   ```bash
   php bin/console lint:twig templates
   php bin/console lint:container
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:schema:validate
   vendor/bin/phpunit
   ```
4. If you changed an entity, generate a migration:
   ```bash
   php bin/console make:migration
   ```
5. Open a pull request. CI must be green.

## Conventions

- Follow the Symfony coding standards and the style of the surrounding code.
- Commit messages: `type: short summary` (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `ci:`).
- Never commit secrets. Configuration goes through environment variables.
