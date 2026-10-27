#!/bin/sh
set -e

echo "Waiting for the database..."
until pg_isready -h database -U app -d hubly >/dev/null 2>&1; do
    sleep 1
done

php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:migrations:migrate --no-interaction

if [ "${LOAD_DEMO_DATA:-0}" = "1" ]; then
    # Only succeeds on the first start: the demo users already exist afterwards (unique e-mail).
    php bin/console doctrine:fixtures:load --group=demo --append --no-interaction \
        || echo "Demo data already loaded, skipping."
fi

php bin/console cache:clear
exec php -S 0.0.0.0:8000 -t public
