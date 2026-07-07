# ecommerce-api

API REST em Laravel para o desafio tecnico de e-commerce.

## Stack com Docker

O projeto pode subir com banco e API em containers.

Na raiz do repositorio:

```bash
docker compose up --build -d
```

Isso disponibiliza:

- API: `http://localhost:8001`
- Demo front-end: `http://localhost:4173`
- MySQL: `127.0.0.1:3307`

## Banco com Docker

O projeto usa um banco `MySQL` em container e a API em Docker se conecta ao servico `db`.

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

## Observacao sobre seed

O `DatabaseSeeder` foi deixado idempotente para suportar o boot automatico do container da API sem duplicar dados demo.
