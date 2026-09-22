#!/bin/sh
set -e

READY_MARKER="storage/framework/docker-ready"

wait_for_database() {
    until php -r "new PDO('pgsql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" >/dev/null 2>&1; do
        echo "Aguardando o banco de dados..."
        sleep 2
    done
}

if [ "$1" = "php-fpm" ]; then
    if [ ! -f vendor/autoload.php ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi

    [ -f .env ] || cp .env.example .env
    grep -q "^APP_KEY=base64" .env || php artisan key:generate --force
    grep -qE "^JWT_SECRET=.+" .env || php artisan jwt:secret --force

    wait_for_database
    php artisan migrate --force

    # O seeder só roda no primeiro boot deste volume: ProductSeeder não é
    # idempotente e reexecutá-lo a cada restart duplicaria os produtos.
    [ -f "$READY_MARKER" ] || php artisan db:seed --force

    # O setup acima roda como root, mas o php-fpm atende requisições como
    # www-data. Sem isso, a regeneração do Swagger (L5_SWAGGER_GENERATE_ALWAYS)
    # falha em tempo de request com "storage directory is not writable".
    chmod -R ugo+rwX storage bootstrap/cache

    php artisan l5-swagger:generate
    mkdir -p "$(dirname "$READY_MARKER")"
    touch "$READY_MARKER"
else
    # Containers de worker reaproveitam esta mesma imagem, então só
    # precisam esperar o container app terminar o setup inicial (install,
    # chaves, migrations) em vez de repeti-lo.
    until [ -f "$READY_MARKER" ]; do
        echo "Aguardando o container app finalizar o setup..."
        sleep 2
    done
fi

exec "$@"
