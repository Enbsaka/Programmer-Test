#!/usr/bin/env sh
set -e

cd /var/www/html

echo "Aguardando o banco de dados ficar disponivel..."
until php -r '
$host = getenv("DB_HOST") ?: "db";
$port = getenv("DB_PORT") ?: "3306";
$database = getenv("DB_DATABASE") ?: "programmer_test";
$username = getenv("DB_USERNAME") ?: "programmer_test_user";
$password = getenv("DB_PASSWORD") ?: "programmer_test_password";

try {
    new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
' >/dev/null 2>&1; do
  sleep 3
done

php artisan config:clear >/dev/null 2>&1 || true
php artisan migrate --force

if [ "${APP_SEED:-true}" = "true" ]; then
  php artisan db:seed --force
fi

exec "$@"
