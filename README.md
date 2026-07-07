# Projeto Vaga - E-commerce

## Estrutura

- `ecommerce-api`: API REST em Laravel 13 com autenticacao via Sanctum, modelagem relacional e seed de dados.
- `demo-api`: painel em Vue 3 + Vite para demonstrar as funcionalidades da API do desafio.

## Como rodar

### Stack completa com Docker

```bash
docker compose up --build -d
```

URLs da stack:

- Front demo: `http://localhost:4173`
- API Laravel: `http://localhost:8001`
- Banco MySQL: `127.0.0.1:3307`

O container da API aguarda o MySQL, aplica `migrate` automaticamente e executa o `DatabaseSeeder` de forma idempotente.

### Banco de dados com Docker

```bash
docker compose up -d db
```

O banco sobe em `127.0.0.1:3307` com as credenciais ja refletidas em `ecommerce-api/.env.example`.

### Back-end

```bash
cd ecommerce-api
php artisan migrate:fresh --seed
php artisan serve
```

Ou, se quiser usar somente Docker para a API:

```bash
docker compose up --build -d api
```

Credenciais seed:

- E-mail: `enzo@example.com`
- Senha: `12345678`

### Front-end

```bash
cd demo-api
npm install
npm run dev
```

Ou, se quiser usar somente Docker para o front:

```bash
docker compose up --build -d demo
```

Se precisar alterar a URL da API localmente:

```bash
VITE_API_URL=http://localhost:8000/api
```

Na stack Docker, o front ja e buildado apontando para `http://localhost:8001/api`.

## Mapeamento do desafio

### 1. Modelagem de Banco

Entidades principais:

- `customers`: clientes do dominio de e-commerce, vinculados ao usuario autenticado.
- `orders`: pedidos de um cliente.
- `products`: catalogo de produtos.
- `categories`: agrupamento de produtos.
- `payments`: pagamentos associados a um pedido.
- `category_product`: tabela pivô para `products <-> categories`.
- `order_product`: tabela pivô para `orders <-> products`, com `quantity` e `unit_price`.

Chaves e normalizacao:

- Todas as tabelas principais usam chave primaria surrogate (`id`) para manter joins simples e consistentes.
- Relacoes `1:N` usam chaves estrangeiras (`orders.customer_id`, `payments.order_id`).
- Relacoes `N:N` usam tabelas pivô com restricoes de unicidade para evitar duplicidade.
- `unit_price` em `order_product` preserva historico do valor no momento da compra.
- O modelo fica em 3FN: dados de cliente, produto, categoria, pedido e pagamento permanecem separados, sem repeticao desnecessaria.

Indices relevantes:

- `customers.email`, `customers.document`: unicidade e busca rapida.
- `products.name`, `products.price`: suporte aos filtros da listagem.
- `orders.status` e `orders(customer_id, created_at)`: consulta de pedidos e analiticos.
- `category_product(category_id, product_id)` e `order_product(order_id, product_id)`: integridade e performance em joins.
- `payments(order_id, status)`: leitura do status de pagamento por pedido.

### 2. API RESTful

Endpoints principais:

- `POST /api/register`
- `POST /api/login`
- `POST /api/logout`
- `GET /api/categories`
- `GET /api/products`
- `GET /api/products/{product}`
- `GET /api/orders`
- `POST /api/orders`
- `GET /api/orders/{order}`
- `PATCH /api/orders/{order}`

Boas praticas implementadas:

- Validacao de filtros, autenticacao e payloads de pedido.
- Tratamento de erros com respostas JSON consistentes.
- Protecao de rotas com `auth:sanctum`.
- Logs basicos de cadastro, login, logout, criacao e atualizacao de pedidos.

### 3. Consultas SQL

Total de pedidos e receita por cliente nos ultimos 12 meses:

```sql
SELECT
    c.id,
    c.name,
    COUNT(o.id) AS total_orders,
    COALESCE(SUM(o.total_amount), 0) AS revenue_last_12_months
FROM customers c
LEFT JOIN orders o
    ON o.customer_id = c.id
   AND o.created_at >= CURRENT_DATE - INTERVAL '12 months'
GROUP BY c.id, c.name
ORDER BY revenue_last_12_months DESC;
```

Produtos mais vendidos por categoria:

```sql
WITH ranked_products AS (
    SELECT
        c.id AS category_id,
        c.name AS category_name,
        p.id AS product_id,
        p.name AS product_name,
        SUM(op.quantity) AS total_sold,
        ROW_NUMBER() OVER (
            PARTITION BY c.id
            ORDER BY SUM(op.quantity) DESC, p.name ASC
        ) AS ranking
    FROM categories c
    JOIN category_product cp ON cp.category_id = c.id
    JOIN products p ON p.id = cp.product_id
    JOIN order_product op ON op.product_id = p.id
    JOIN orders o ON o.id = op.order_id
    GROUP BY c.id, c.name, p.id, p.name
)
SELECT
    category_id,
    category_name,
    product_id,
    product_name,
    total_sold
FROM ranked_products
WHERE ranking = 1
ORDER BY category_name;
```

### 4. Integracao Externa

- `GET /api/cep/{cep}` usa o `ViaCepService` como integracao adicional, com `timeout`, `retry`, log e tratamento de falhas.
- A tela `Desafio e Demo` no front permite demonstrar essa integracao ao vivo consultando um CEP real pela API Laravel.
- Em um gateway de pagamento real, o mesmo padrao deve incluir:
  - idempotencia por pedido;
  - retries com backoff;
  - circuit breaker ou fila para indisponibilidade externa;
  - observabilidade via logs e alertas;
  - reconciliacao assicrona para estados pendentes.

## Harmonia entre front e back

- O front consome apenas endpoints reais da API.
- A interface foi simplificada para servir como painel de demonstracao do desafio tecnico.
- O painel centraliza autenticacao, filtros de produtos, criacao de pedidos, listagem de pedidos e consulta de CEP.
- A demonstracao do ViaCEP consome `GET /cep/{cep}` e exibe o retorno do back-end no front.
- O token Sanctum e enviado automaticamente pelo Axios.

## Comandos uteis

### Banco Docker

```bash
cd ecommerce-api
composer db:up
composer db:fresh
composer db:down
```

### Stack Docker completa

```bash
docker compose up --build -d
docker compose down
```
