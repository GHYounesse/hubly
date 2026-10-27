# Deploying the demo

The repository ships a Dockerfile and a `fly.toml` to host a public demo on [Fly.io](https://fly.io).
It uses PHP's built-in web server, which is fine for a demo and not for real traffic: for production put
nginx or Caddy with php-fpm in front instead.

## One-time setup

```bash
fly auth login
fly apps create hubly-demo            # then put the same name in fly.toml
fly postgres create --name hubly-demo-db
fly postgres attach hubly-demo-db --app hubly-demo   # sets DATABASE_URL
```

Secrets (never committed):

```bash
fly secrets set --app hubly-demo \
  APP_SECRET="$(php -r 'echo bin2hex(random_bytes(16));')" \
  APP_ENCRYPTION_KEY="$(php bin/console app:encryption:generate-key | head -1 | cut -d= -f2)" \
  JWT_PASSPHRASE="$(php -r 'echo bin2hex(random_bytes(12));')" \
  JWT_SECRET_KEY=/app/config/jwt/private.pem \
  JWT_PUBLIC_KEY=/app/config/jwt/public.pem \
  MAIL_FROM=no-reply@example.com ALERT_RECIPIENT=you@example.com
```

## Deploy

```bash
fly deploy
```

or from GitHub: add the `FLY_API_TOKEN` secret (`fly tokens create deploy`) to a repository environment named
`demo`, then run the **Deploy demo** workflow from the Actions tab.

The demo data (`LOAD_DEMO_DATA=1`) is loaded on the first start. Remove that variable from `fly.toml` for a
real instance and create your administrator with `fly ssh console -C "php bin/console app:create-user ..."`.

## Checks

- `/health` answers `{"status":"ok","database":"ok"}` (also used by Fly as its health check).
- The scheduled checks need a worker: run `php bin/console messenger:consume scheduler_default`
  as a second process or machine.
