<p align="center">
  <img src="public/images/hubly-mark.svg" alt="Hubly logo" width="90">
</p>

# Hubly

[![CI](https://github.com/GHYounesse/hubly/actions/workflows/ci.yml/badge.svg)](https://github.com/GHYounesse/hubly/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A5%208.2-777bb4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.2-000000?logo=symfony)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-38bdf8?logo=tailwindcss&logoColor=white)

**Hubly** is a web platform that centralizes the management of projects, client
companies, developers, technologies and hosting. It alerts you before a hosting
plan expires, checks that project URLs are online, keeps an audit history of every
change, and secures access with roles and two-factor authentication.

<!-- Add screenshots in docs/screenshots/ and reference them here:
![Dashboard](docs/screenshots/dashboard.png)
-->

## Features

- **Projects, companies, developers, technologies, hosting**: full CRUD, with relations between them
- **Search and filters** with pagination on the main lists
- **Authentication**: login, password reset by e-mail, CSRF protection, "remember me"
- **Two-factor authentication**: a 6-digit code sent by e-mail, valid for 5 minutes
- **Roles**: `ROLE_ADMIN` and `ROLE_USER`, each with its own dashboard
- **Hosting expiration alerts**: in-app notification and e-mail 15 days before a plan expires
- **URL monitoring**: checks each project URL, raises a notification when it is unreachable and keeps the
  result of every check, so each project shows its availability history (uptime % over 30 days, response time)
- **Audit trail**: a Doctrine listener records every creation, change and deletion (who, when) and, for changes,
  the before / after value of each field. Timestamps and secrets (password, 2FA code) are never stored
- **REST API** with JWT authentication, OpenAPI documentation, filters, pagination and rate limiting
- **Encrypted credentials**: hosting and project access notes are encrypted in the database (libsodium)
- **SSL certificate monitoring**: the expiry of each project's TLS certificate is tracked and alerted
- **Per-company access**: users only open the companies assigned to them (Symfony Voter), in the web app and in the API
- **Alerts where you work**: e-mail, an optional Slack channel and a daily digest, sent by a scheduler
- **Planning view and CSV export**: every project on one timeline, lists exported for spreadsheets
- **Hosting costs**: price and billing period per plan, yearly renewal budget on the dashboard
- **Console commands** designed to run from cron or the scheduler
- **Light and dark theme**: one palette defined as CSS variables in `assets/styles/theme.css`, shared with Tailwind, switchable from the navbar

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, Symfony 7.2, Doctrine ORM 3 + Migrations |
| Templates | Twig |
| Front | Tailwind CSS 3.4, Stimulus, AssetMapper |
| Database | PostgreSQL 16 |
| API | API Platform 4, JWT (LexikJWTAuthenticationBundle), Symfony Rate Limiter |
| Mail | Symfony Mailer (SMTP / Gmail) |
| Tests / CI | PHPUnit, GitHub Actions |

## Try it with demo data

One command starts the app, PostgreSQL and a small demo agency (clients, projects, hosting plans, alerts and an
uptime history):

```bash
docker compose -f docker-compose.demo.yml up --build
```

Open <http://localhost:8000> and sign in with `admin@hubly.test` / `Demo1234!` (or `equipe@hubly.test` for a
regular user). The 2FA code is written to the log:

```bash
docker compose -f docker-compose.demo.yml exec app tail -n 20 var/log/dev.log
```

Without Docker, load the same data into your own database with
`php bin/console doctrine:fixtures:load --group=demo`.

## Getting started

### Prerequisites

- PHP 8.2+ with `ctype`, `iconv`, `intl` and `pdo_pgsql`
- Composer 2
- Node.js 18+ and npm
- PostgreSQL 16 (a PostgreSQL service is provided in `compose.yaml`: `docker compose up -d database`)
- [Symfony CLI](https://symfony.com/download) (optional, recommended)

### Installation

```bash
git clone https://github.com/GHYounesse/hubly.git
cd hubly

composer install
npm install
```

### Configuration

```bash
cp .env.example .env
```

Then edit `.env`. It is git-ignored, so your secrets stay local.

| Variable | Description |
|---|---|
| `APP_SECRET` | Random secret, e.g. `php -r "echo bin2hex(random_bytes(16));"` |
| `DATABASE_URL` | e.g. `postgresql://app:!ChangeMe!@127.0.0.1:5432/hubly?serverVersion=16&charset=utf8` |
| `MAILER_DSN` | `null://null` to send nothing, or your SMTP / Gmail DSN |
| `MAIL_FROM` | Sender address of every e-mail (2FA codes, alerts) |
| `ALERT_RECIPIENT` | Who receives hosting-expiration and URL-down alerts |

### Database

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Run

```bash
npm run build        # compile Tailwind once (use `npm run dev` to watch)
symfony serve        # or: php -S 127.0.0.1:8000 -t public
```

Open <http://127.0.0.1:8000>.

## First login

There is no public sign-up, so create the first account from the console:

```bash
php bin/console app:create-user admin@example.com 'ChooseAPassword' --admin --prenom=Ada --nom=Admin
```

Log in at `/login`. A 6-digit code is then e-mailed to you (two-factor authentication).
In development, point `MAILER_DSN` at the Mailpit container (`smtp://127.0.0.1:1025`, web UI on
port 8025) to read it, or read it from the `two_factor_code` column of the `app_user` table.

## REST API

Projects, companies and hosting plans are available as a JSON REST API built with
[API Platform](https://api-platform.com). Interactive OpenAPI documentation (Swagger UI) is served at
`/api/docs`, and the raw specification at `/api/docs.jsonopenapi`.

Generate the JWT key pair once (the keys are git-ignored):

```bash
php bin/console lexik:jwt:generate-keypair
```

```bash
# 1. get a token (valid one hour)
curl -s -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@hubly.test","password":"Demo1234!"}'

# 2. use it
curl -s http://localhost:8000/api/projects?status=en%20cours \
  -H 'Authorization: Bearer <token>' -H 'Accept: application/json'
```

| Resource | Endpoints | Notes |
|---|---|---|
| Projects | `GET/POST /api/projects`, `GET/PATCH/DELETE /api/projects/{id}` | filters: `nom`, `status`, `priorite`, `societe`, `dateLivraison[before\|after]`; sort with `order[nom]=asc` |
| Companies | `GET /api/companies`, `GET/PATCH /api/companies/{id}` | created in the web app (logo upload) |
| Hosting | `GET/POST /api/hostings`, `GET/PATCH /api/hostings/{id}` | filters: `site`, `fournisseur`, `estActif`, `dateExpiration[before\|after]` |

- **Roles:** any user can read, only `ROLE_ADMIN` can create, update or delete.
- **Secrets stay private:** hosting credentials and project source-code/environment access are never exposed.
- **Rate limiting:** 120 requests per minute per user (per IP when anonymous), reported in the
  `X-RateLimit-Limit` and `X-RateLimit-Remaining` headers; a rejected request gets `429` with `Retry-After`.
  Login is limited to 5 attempts per minute.
- **2FA:** the web login asks for an e-mailed code; API login is password only (tokens are for scripts and
  integrations). Use a dedicated, least-privileged account for integrations.

## Encryption key

Hosting and project credentials are encrypted before they reach the database. Generate a key once and keep it
safe (a password manager, your hosting secrets): without it the stored credentials cannot be read again.

```bash
php bin/console app:encryption:generate-key
```

Put the printed `APP_ENCRYPTION_KEY=...` line in `.env`. If you already have plain-text credentials stored,
encrypt them once with `php bin/console app:encryption:encrypt-existing`.

## Scheduled checks

The checks run on their own through the Symfony Scheduler (see `src/Schedule/MainSchedule.php`). Start the worker:

```bash
php bin/console messenger:consume scheduler_default -vv
```

| Every | What |
|---|---|
| 15 minutes | check every project URL and store the result (uptime history) |
| day, 03:00 | read the SSL certificate expiry of every https project |
| day, 04:00 | delete uptime history older than 90 days |
| day, 08:00 | alert about hosting plans that expire soon |
| day, 08:15 | send the daily digest (e-mail, and Slack when `SLACK_DSN` is set) |

Each task is also a console command if you prefer cron: `app:check-urls`, `app:check-ssl`,
`app:check-expirations`, `app:send-digest`.

## Tests and quality

```bash
vendor/bin/phpunit
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console lint:container
php bin/console doctrine:schema:validate
```

The same checks run on every push and pull request through
[GitHub Actions](.github/workflows/ci.yml).

## Project structure

```
src/
  Command/           app:check-expirations, app:check-urls
  Controller/        grouped by domain: Account, Auth, Company, Dashboard, Developer,
                     Error, Home, Hosting, Monitoring, Project, Search, Technology
  Entity/            Doctrine entities
  EventSubscriber/   audit trail, notifications, 2FA guard
  Service/           grouped by domain: Monitoring, Notification, Security
templates/           Twig views (layout/ for the shells, one folder per feature)
assets/              Stimulus controllers and Tailwind input
tests/               PHPUnit tests, mirroring src/
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the conventions, and `make help` for the day-to-day commands.

## Roadmap

- [x] Doctrine migrations committed (initial schema)
- [ ] Fixtures for demo data
- [ ] Live search and dashboard charts (Symfony UX Turbo / Chart.js)
- [ ] More automated tests (services, controllers)
- [ ] Docker setup for the whole stack

## License

Released under the [MIT License](LICENSE).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). To report a security issue, read
[SECURITY.md](SECURITY.md).
