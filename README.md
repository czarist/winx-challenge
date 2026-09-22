# Product API

API RESTful para gerenciamento de produtos, desenvolvida em Laravel 13 como parte do desafio técnico "Desenvolvedor(a) Back-End Sênior". Cobre CRUD completo de produtos, paginação, filtros avançados, autenticação via JWT, documentação interativa (Swagger/OpenAPI) e auditoria assíncrona de criação/atualização/exclusão via fila.

## Sumário

- [Stack](#stack)
- [Arquitetura](#arquitetura)
- [Setup com Docker](#setup-com-docker)
- [Setup local](#setup-local)
- [Documentação interativa (Swagger)](#documentação-interativa-swagger)
- [Testes automatizados](#testes-automatizados)
- [Exemplos de chamadas de API](#exemplos-de-chamadas-de-api)
- [Diferenciais implementados](#diferenciais-implementados)

## Stack

- PHP 8.3 / Laravel 13
- PostgreSQL 16
- `tymon/jwt-auth` para autenticação
- `darkaonline/l5-swagger` para documentação OpenAPI
- Docker + Docker Compose (PHP-FPM, Nginx, Postgres, worker de fila)
- PHPUnit para testes automatizados

## Arquitetura

```
app/
├── DataTransferObjects/   # ProductFilters, AuthResult — tipagem forte em vez de arrays soltos
├── Enums/                 # ProductLogAction
├── Exceptions/            # ApiExceptionRenderer — resposta JSON padronizada para todo erro
├── Http/
│   ├── Controllers/Api/V1 # Controllers finos, sem regra de negócio
│   ├── Requests/          # Form Requests — toda validação de entrada, mensagens em pt-BR
│   └── Resources/         # API Resources — toda resposta de dados
├── Jobs/                  # LogProductActivity — log assíncrono (queue), nunca síncrono no controller
├── Models/
├── OpenApi/                # Definições globais do Swagger (info, security, schemas compartilhados)
├── Providers/              # Bindings de interface → implementação
├── Repositories/           # Acesso a dados (Eloquent) isolado atrás de uma interface
└── Services/                # Regra de negócio, isolada atrás de uma interface
```

Controller → Service → Repository, cada camada dependendo de uma interface (`ProductRepositoryInterface`, `ProductServiceInterface`, `AuthServiceInterface`), com a implementação concreta resolvida via container em `AppServiceProvider`. Isso mantém a regra de negócio testável e trocável (ex.: dá pra substituir o repositório do Postgres por outro sem tocar no controller) sem forçar padrões desnecessários em cima disso.

O job de auditoria (`LogProductActivity`) é sempre despachado pela `queue`, nunca executado de forma síncrona — inclusive na exclusão, quando o produto já não existe mais no banco no momento em que o job roda (por isso `product_logs.product_id` não tem foreign key: é um registro histórico, não uma relação viva).

## Setup com Docker

Pré-requisito: Docker e Docker Compose.

```bash
cp .env.example .env
docker compose up -d --build
```

O container `app` cuida de tudo sozinho no primeiro boot: instala dependências (se `vendor/` não existir), gera `APP_KEY`/`JWT_SECRET` (se ausentes), espera o Postgres ficar pronto, roda as migrations, popula o banco (só na primeira vez) e gera a documentação do Swagger — antes de subir o PHP-FPM. O Nginx só começa a aceitar tráfego depois que esse setup termina (via healthcheck), então não há corrida entre os containers.

Serviços subidos:

| Serviço  | Descrição                                   |
|----------|----------------------------------------------|
| `app`    | PHP-FPM 8.3                                   |
| `nginx`  | Serve a aplicação em `http://localhost:8000`  |
| `pgsql`  | PostgreSQL 16                                 |
| `queue`  | `php artisan queue:work` em container separado|

Se as portas `8000` ou `5432` já estiverem em uso na sua máquina, ajuste `APP_FORWARD_PORT`/`DB_FORWARD_PORT` no `.env` antes de subir os containers — não é preciso alterar nenhum código.

Comandos úteis:

```bash
docker compose logs -f app        # acompanhar o boot / logs da aplicação
docker compose logs -f queue      # acompanhar o worker de fila
docker compose exec app php artisan migrate:fresh --seed   # resetar o banco
docker compose down               # parar tudo (mantém o volume do Postgres)
docker compose down -v            # parar tudo e apagar os dados do Postgres
```

## Setup local

Pré-requisitos: PHP 8.3+, Composer, extensão `pdo_pgsql`, e um PostgreSQL acessível (local ou remoto).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Edite o `.env` para apontar para o seu Postgres — o único ajuste necessário em relação ao padrão do arquivo é trocar `DB_HOST=pgsql` (nome do serviço no Docker) por `DB_HOST=127.0.0.1` (ou o host do seu banco). O restante (`DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) já funciona com um Postgres local criado com essas mesmas credenciais, ou ajuste conforme o seu ambiente.

```bash
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

Em um segundo terminal, o worker da fila (obrigatório para os logs de auditoria serem gravados):

```bash
php artisan queue:work
```

A aplicação sobe em `http://localhost:8000`.

## Documentação interativa (Swagger)

Com a aplicação no ar (Docker ou local):

- UI: `http://localhost:8000/api/documentation`
- JSON puro (OpenAPI 3): `http://localhost:8000/docs`

A documentação é regenerada automaticamente a cada request (`L5_SWAGGER_GENERATE_ALWAYS=true` no `.env.example`, pensado para desenvolvimento). Para gerar manualmente:

```bash
php artisan l5-swagger:generate
# ou, via Docker:
docker compose exec app php artisan l5-swagger:generate
```

Pela própria UI é possível se autenticar: registre/logue por `/auth/register` ou `/auth/login`, copie o `access_token` da resposta e cole no botão **Authorize** (esquema `bearerAuth`) para testar os endpoints de produtos diretamente pelo navegador.

## Testes automatizados

```bash
php artisan test
```

Roda contra SQLite em memória (configurado em `phpunit.xml`), sem precisar de um Postgres disponível — não depende do Docker nem de um banco local para os testes. Cobre autenticação (registro, login, logout, refresh, incluindo blacklist de token) e as regras de negócio de produtos (CRUD, filtros, paginação, autorização e o disparo do job de auditoria).

## Exemplos de chamadas de API

Usuário de teste criado pelo seeder: `teste@productapi.com` / `password123`.

### Autenticação

**Registro**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Maria Silva",
    "email": "maria@example.com",
    "password": "senha12345",
    "password_confirmation": "senha12345"
  }'
```

**Login**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "teste@productapi.com", "password": "password123"}'
```

Resposta (register/login/refresh têm o mesmo formato):
```json
{
  "user": { "id": 1, "name": "Usuário de Teste", "email": "teste@productapi.com", "created_at": "..." },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Refresh** (aceita um token já expirado, desde que dentro do `refresh_ttl`)
```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Authorization: Bearer {access_token}"
```

**Logout** (invalida o token atual)
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {access_token}"
```

### Produtos

Todos os endpoints abaixo exigem `Authorization: Bearer {access_token}`.

**Listar com filtros e paginação**
```bash
curl "http://localhost:8000/api/v1/products?search=mouse&categoria=Periféricos&preco_min=50&preco_max=500&em_estoque=true&per_page=10" \
  -H "Authorization: Bearer {access_token}"
```

| Parâmetro    | Descrição                                          |
|--------------|-----------------------------------------------------|
| `search`     | Busca por nome (case-insensitive)                   |
| `categoria`  | Filtra por categoria exata                           |
| `preco_min`  | Preço mínimo                                         |
| `preco_max`  | Preço máximo                                         |
| `em_estoque` | `true`/`false` — disponibilidade em estoque          |
| `per_page`   | Itens por página (1–100, padrão 15)                  |
| `page`       | Página atual                                         |

**Criar**
```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Teclado mecânico",
    "descricao": "Switches azuis, layout ABNT2",
    "preco": 349.90,
    "categoria": "Periféricos",
    "estoque": 20
  }'
```

**Detalhe**
```bash
curl http://localhost:8000/api/v1/products/1 -H "Authorization: Bearer {access_token}"
```

**Atualizar** (aceita atualização parcial — envie só os campos que quer mudar)
```bash
curl -X PUT http://localhost:8000/api/v1/products/1 \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{"preco": 329.90, "estoque": 15}'
```

**Excluir**
```bash
curl -X DELETE http://localhost:8000/api/v1/products/1 -H "Authorization: Bearer {access_token}"
```

### Formato de erro

Toda resposta de erro segue o mesmo formato, independente da causa (validação, autenticação, recurso não encontrado, etc.):

```json
{
  "message": "Os dados informados são inválidos.",
  "errors": { "preco": ["O preço não pode ser negativo."] }
}
```

`errors` só aparece quando a falha é de validação (422); nos demais casos (401, 404, 405, 429, 500) a resposta traz apenas `message`.

## Diferenciais implementados

- **JWT completo**: register/login/logout/refresh, com blacklist de token (logout e refresh invalidam o token anterior) — não apenas o login básico pedido no desafio.
- **Swagger/OpenAPI**: todos os endpoints documentados via PHP Attributes, com schemas de request/response, exemplos e autenticação testável direto pela UI.
- **Repository/Service por trás de interfaces**: injeção de dependência via `AppServiceProvider`, sem acoplar a regra de negócio ao Eloquent nem os controllers à implementação concreta.
- **Testes automatizados**: 44 testes (PHPUnit) cobrindo autenticação e as regras de negócio de produtos, rodando sem depender de Postgres.
- **Rate limiting**: 60 requisições/minuto por usuário (ou IP, se não autenticado) nas rotas de API.
- **Execução dual sem alteração de código**: um único `docker-compose.yml` e `.env.example` cobrem Docker e execução local; o `app` container faz o próprio setup (install, migrate, seed, permissões, docs) e o Nginx só recebe tráfego depois que esse setup termina de verdade.
- **Busca portável**: filtro de nome usa `LOWER()+LIKE` em vez de `ILIKE` (específico do Postgres), então o mesmo código funciona nos testes (SQLite) e em produção (Postgres) sem sacrificar a busca case-insensitive.

**Não implementado:** Elasticsearch. A busca por nome atende ao requisito funcional pedido (via Postgres), mas ranking de relevância e sugestões ficaram fora por tempo — ficaria em uma camada adicional por trás da mesma `ProductRepositoryInterface`, sem precisar tocar no controller ou no service.
