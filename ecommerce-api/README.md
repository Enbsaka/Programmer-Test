# ecommerce-api

API REST em Laravel para o desafio tecnico de e-commerce.

## Banco com Docker

O projeto usa um banco `MySQL` em container.

Na raiz do projeto:

```bash
docker compose up -d db
```

Ou a partir desta pasta:

```bash
composer db:up
```

## Configuracao

O `.env.example` ja vem preparado para conectar na instancia Docker:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=programmer_test
DB_USERNAME=programmer_test_user
DB_PASSWORD=programmer_test_password
```

## Fluxo local

```bash
composer install
cp .env.example .env
php artisan key:generate
composer db:up
php artisan migrate:fresh --seed
php artisan serve
```

## Comandos uteis

```bash
composer db:up
composer db:fresh
composer db:down
php artisan test
```
