# Onfly — API de Pedidos Onfly

API REST em Laravel para o teste de desenvolvimento Onfly, com autenticação via JWT, isolamento de dados por usuário e visão global para administradores.

## Sumário

- [Stack](#stack)
- [Pré-requisitos](#pré-requisitos)
- [Subindo o projeto](#subindo-o-projeto)
- [Portas e URLs](#portas-e-urls)
- [Helper `./run`](#helper-run)
- [Usuários de teste](#usuários-de-teste)
- [Autenticação](#autenticação)
- [Endpoints](#endpoints)
- [Collection do Postman](#collection-do-postman)
- [Documentação Swagger](#documentação-swagger)
- [Testes](#testes)
- [Regras de negócio](#regras-de-negócio)

## Stack

- **PHP 8.3** / **Laravel**
- **MySQL 8.0**
- **Nginx** (proxy para o PHP-FPM)
- **Docker / Docker Compose**
- Autenticação JWT (`firebase/php-jwt`)
- DTOs com `spatie/laravel-data`
- Documentação OpenAPI com `darkaonline/l5-swagger`

## Pré-requisitos

- Docker
- Docker Compose

Nada mais precisa estar instalado na máquina (PHP, Composer e MySQL rodam dentro dos containers).

## Subindo o projeto

Na primeira vez (ou após mudanças no `Dockerfile`), suba com build:

```bash
docker compose up -d --build
```

Nas próximas vezes basta:

```bash
docker compose up -d
```

O container da aplicação executa automaticamente, no boot, todo o necessário para o projeto ficar pronto (script `docker/php/start_project.sh`):

1. Cria os diretórios de `storage` e ajusta permissões;
2. Cria o `.env` a partir do `.env.example` (se ainda não existir);
3. Instala as dependências do Composer (se `vendor/` não existir);
4. Gera a `APP_KEY` (se estiver vazia);
5. Roda as **migrations** e os **seeders** (a tabela `order_status` já é populada com os status `Solicitado`, `Aprovado`, `Cancelado`);
6. Limpa caches (`optimize:clear`);
7. Gera a documentação **Swagger**;
8. Sobe o PHP-FPM.

> Não é necessário rodar nenhum comando manual após o `docker compose up`. O projeto sobe pronto para uso.

Para acompanhar o boot:

```bash
docker logs -f onfly_app
```

Para derrubar tudo (incluindo o banco):

```bash
docker compose down -v
```

## Portas e URLs

| Serviço | URL / Porta |
|---|---|
| API (via Nginx) | http://localhost:8080 |
| Documentação Swagger | http://localhost:8080/api/documentation |
| MySQL (host) | `localhost:3307` (dentro da rede Docker: `mysql:3306`) |

Credenciais do banco (definidas no `docker-compose.yaml`): database `onfly`, usuário `onfly_user`, senha `onfly_user_1`.

## Helper `./run`

O script `./run` executa qualquer comando dentro do container da aplicação:

```bash
./run php artisan migrate
./run php artisan test
./run composer install
```

## Usuários de teste

Para gerar rapidamente um usuário **cliente** e um **admin** (com as respectivas `api_key`), rode:

```bash
./run php artisan app:create-users
```

Isso cria:

| Tipo | E-mail | is_admin |
|---|---|---|
| Cliente | `cliente@onfly.com.br` | Não |
| Admin | `admin@onfly.com.br` | Sim |

O comando imprime no terminal a **`api_key`** de cada um — guarde-a, pois ela só é exibida uma vez. Use-a para obter o token JWT (veja abaixo).

Você também pode criar usuários pela rota pública `POST /usuario` (a `api_key` é retornada na resposta).

## Autenticação

A API usa um fluxo em duas etapas:

1. Cada usuário possui uma **`api_key`** (obtida no cadastro ou via comando de teste).
2. A `api_key` é trocada por um **token JWT** em `POST /auth/token`. O token (header `Authorization: Bearer <token>`) autentica as rotas protegidas.

> **Header obrigatório em todas as requisições:** `X-Request-Id` (qualquer identificador da requisição). Sem ele, a API responde **400**.

### Obtendo o token

```bash
curl -X POST http://localhost:8080/auth/token \
  -H "X-Request-Id: req-1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"x_api_key":"SUA_API_KEY"}'
```

Resposta:

```json
{
  "access_token": "eyJ0eXAiOiJKV1Q...",
  "token_type": "Bearer",
  "expires_in": "3600"
}
```

## Endpoints

Todas as rotas exigem o header `X-Request-Id`. As rotas de pedido exigem também `Authorization: Bearer <token>`.

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/ping` | — | Health check |
| `POST` | `/usuario` | — | Cadastro de usuário |
| `POST` | `/auth/token` | — | Troca `api_key` por JWT |
| `GET` | `/pedido` | JWT | Lista pedidos (admin vê todos) |
| `GET` | `/pedido/{referencia_pedido}` | JWT | Detalha um pedido |
| `POST` | `/pedido` | JWT | Cria um pedido |
| `PATCH` | `/pedido/{referencia_pedido}/status` | JWT + Admin | Atualiza status do pedido |

### `POST /usuario` — cadastro

```bash
curl -X POST http://localhost:8080/usuario \
  -H "X-Request-Id: req-1" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "nome": "Maria Cliente",
    "email": "maria@onfly.com.br",
    "password": "senha12345",
    "password_confirmation": "senha12345"
  }'
```

Campos: `nome` (obrigatório), `email` (obrigatório, único), `password` (mínimo 8, requer `password_confirmation`), `is_admin` (opcional, boolean). Resposta **201** com os dados do usuário e a `api_key` (exibida uma única vez):

```json
{
  "data": {
    "ref": "usr-...",
    "name": "Maria Cliente",
    "email": "maria@onfly.com.br",
    "is_admin": false,
    "api_key": "AQcL0aia...",
    "criado_em": "2026-06-22 20:02:56"
  }
}
```

### `POST /pedido` — criar pedido

```bash
curl -X POST http://localhost:8080/pedido \
  -H "X-Request-Id: req-1" -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "solicitante": "Maria Cliente",
    "data_partida": "20-06-2026",
    "data_retorno": "25-06-2026",
    "destino": { "cidade": "São Paulo", "estado": "SP", "pais": "Brasil" }
  }'
```

Datas no formato **`DD-MM-YYYY`**. `data_retorno` deve ser igual ou posterior à `data_partida`.

### `GET /pedido` — listar (com filtros)

Filtros opcionais via query string:

- `status` — `1` (Solicitado), `2` (Aprovado) ou `3` (Cancelado)
- `data_inicio` — `DD-MM-YYYY`
- `data_fim` — `DD-MM-YYYY` (igual ou posterior a `data_inicio`)

```bash
curl "http://localhost:8080/pedido?status=1&data_inicio=01-06-2026&data_fim=30-06-2026" \
  -H "X-Request-Id: req-1" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

Um usuário comum vê apenas os **próprios** pedidos. Um **admin** enxerga os pedidos de **todos** os usuários.

### `PATCH /pedido/{referencia_pedido}/status` — atualizar status (admin)

```bash
curl -X PATCH http://localhost:8080/pedido/ped-XXXX/status \
  -H "X-Request-Id: req-1" -H "Authorization: Bearer $TOKEN_ADMIN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"status": 2}'
```

`status`: `2` (Aprovado) ou `3` (Cancelado). Exige usuário **admin** (caso contrário, **403**).

## Collection do Postman

O arquivo **`Onfly Teste.postman_collection.json`** (na raiz do projeto) é uma collection do Postman com exemplos de todas as rotas da API.

Para usá-la:

1. No Postman, vá em **Import** e selecione o arquivo `Onfly Teste.postman_collection.json`.
2. Ajuste a URL base para `http://localhost:8080` (caso necessário) e informe o header `X-Request-Id` exigido em todas as requisições.
3. Obtenha o token em `POST /auth/token` e use-o (header `Authorization: Bearer <token>`) nas rotas protegidas de pedido.

## Documentação Swagger

A documentação interativa fica em **http://localhost:8080/api/documentation** e é gerada automaticamente no boot. Para regerar manualmente após mudar anotações:

```bash
./run php artisan l5-swagger:generate
```

## Testes

```bash
./run php artisan test
```

A suíte cobre os fluxos de cadastro, autenticação, criação/listagem/detalhe de pedidos, atualização de status, isolamento de dados e a visão de admin.

## Regras de negócio

Implementadas:

- Autenticação via JWT.
- **Isolamento de dados:** cada usuário só enxerga os próprios pedidos.
- **Admin:** enxerga os pedidos de todos os usuários e é o único que pode atualizar o status de um pedido.
- O admin **não pode** alterar o status do **próprio** pedido (responde **403**).
- **Cancelamento condicional:** o cancelamento de um pedido só é permitido enquanto ele não tiver sido aprovado. Tentar cancelar um pedido já aprovado retorna **409 Conflict**.
- **Notificação de status:** o usuário solicitante é notificado (e-mail + registro em banco) sempre que seu pedido é aprovado ou cancelado. O envio é síncrono (sem fila).
