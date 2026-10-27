# Architecture

Hubly follows the standard Symfony layout. Code is grouped **by domain** inside each
layer, so a feature is easy to find and a layer never becomes a flat list of dozens of files.

```
config/                  Symfony configuration (one file per package)
  api_platform/          REST API resources (which operations, who may call them)
  serializer/            Which fields each API resource exposes (groups)
migrations/              Doctrine migrations, one per logical schema change
public/                  Web root: front controller, bundled admin theme, built CSS
src/
  Command/               Console commands (cron entry points)
  Controller/            HTTP layer, grouped by domain
    Account/ Auth/ Company/ Dashboard/ Developer/ Error/
    Home/ Hosting/ Monitoring/ Project/ Search/ Technology/
  Entity/                Doctrine entities (one class per file, file = class name)
  EventListener/         Symfony event listeners
  EventSubscriber/       Doctrine and kernel subscribers (audit trail, notifications, 2FA)
  Form/                  Form types
  Repository/            Doctrine repositories (all queries live here or in services)
  Service/               Application services, grouped by domain
    Monitoring/          Hosting expiration and URL checks
    Notification/        In-app notifications and e-mail sending
    Security/            Two-factor codes
  Message/ MessageHandler/ Schedule/   Scheduled work (Symfony Scheduler + Messenger)
  Security/              Field encryption and the company access Voter
  Doctrine/              Custom DBAL type (encrypted_text) and API query extensions
  Twig/                  Twig extensions
templates/
  base.html.twig         Public and authentication pages
  layout/                Application shells: admin.html.twig, user.html.twig,
                         plus their navbar / sidebar / footer and the theme partials
  <feature>/             One folder per feature (projets, hebergement, ...)
  bundles/               Overrides of bundle templates (error pages)
assets/                  AssetMapper entry points, Stimulus controllers, styles/theme.css, Tailwind input
tests/                   Mirrors src/ (tests/Monitoring tests src/Service/Monitoring)
docs/                    Project documentation
```

## Conventions

- **Controllers stay thin**: read the request, call a service or repository, render a
  template. Business rules belong in `Service/`, queries in `Repository/`.
- **One class per file**, file name equals class name (PSR-4).
- **Namespaces follow folders**: `src/Controller/Project/ProjetController.php` is
  `App\Controller\Project\ProjetController`.
- **Route names are stable** (`app_<feature>_<action>`) and defined once.
- **Templates are referenced from the matching controller domain**; shared layouts live in
  `templates/layout/`.
- **Configuration comes from the environment**: no secrets or e-mail addresses in code,
  see `.env.example`.
- **Every schema change ships with a migration**, and CI checks that the schema matches
  the entities (`doctrine:schema:validate`).

## Day-to-day commands

`make help` lists them: `make install`, `make db`, `make test`, `make lint`, `make cs`, `make stan`.
