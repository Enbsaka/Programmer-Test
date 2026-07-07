# demo-api

Painel em Vue 3 + Vite usado para demonstrar visualmente as funcionalidades da API do desafio tecnico.

## Scripts

```bash
npm install
npm run dev
npm run build
```

## Configuracao

Por padrao, o front consome:

```bash
http://localhost:8000/api
```

Para alterar:

```bash
VITE_API_URL=http://localhost:8000/api
```

## Docker

Na raiz do repositorio:

```bash
docker compose up --build -d demo
```

A interface fica disponivel em:

```bash
http://localhost:4173
```

Na stack Docker, o build do front ja sai apontando para:

```bash
http://localhost:8001/api
```
