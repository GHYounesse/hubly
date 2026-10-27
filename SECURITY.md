# Security Policy

## Reporting a vulnerability

Please **do not open a public issue** for security problems.

Use GitHub's private reporting instead: go to the **Security** tab of this
repository and click **Report a vulnerability**. Include the steps to reproduce
and the impact, and you will get an answer as soon as possible.

## Secrets

This repository must never contain real credentials. All secrets (database URL,
mailer DSN, `APP_SECRET`) live in your local `.env`, which is git-ignored. If
you find a committed secret, report it as described above so it can be rotated.
