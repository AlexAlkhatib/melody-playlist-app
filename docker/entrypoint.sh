#!/bin/sh
# ─────────────────────────────────────────────────────────────────────────────
# Melody — point d'entrée du conteneur
#  1. amorce le volume public partagé avec nginx (assets + uploads) ;
#  2. attend que la base de données réponde ;
#  3. applique les migrations ;
#  4. démarre php-fpm.
# ─────────────────────────────────────────────────────────────────────────────
set -e

APP_ENV="${APP_ENV:-prod}"
PUBLIC_DIR="/var/www/html/public"
SEED_DIR="/opt/public-seed"

if [ ! -f "${PUBLIC_DIR}/index.php" ]; then
    echo "→ Initialisation du volume public…"
    mkdir -p "${PUBLIC_DIR}"
    cp -a "${SEED_DIR}/." "${PUBLIC_DIR}/"
fi

mkdir -p "${PUBLIC_DIR}/media/images" "${PUBLIC_DIR}/media/morceaux" \
         /var/www/html/var/cache /var/www/html/var/log

if [ "${WAIT_FOR_DB:-1}" = "1" ]; then
    echo "→ Attente de la base de données…"
    i=0
    until php bin/console doctrine:query:sql "SELECT 1" --env="${APP_ENV}" >/dev/null 2>&1; do
        i=$((i + 1))
        if [ "${i}" -ge 20 ]; then
            echo "⚠ Base injoignable après 60 s : démarrage poursuivi."
            break
        fi
        sleep 3
    done
fi

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    echo "→ Application des migrations…"
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env="${APP_ENV}" \
        || echo "⚠ Migrations non appliquées."
fi

php bin/console cache:warmup --env="${APP_ENV}" --no-debug || true

echo "→ Démarrage de PHP-FPM"
exec "$@"
