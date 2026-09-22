<div align="center">

# Product API

RESTful product management API — full CRUD, advanced filters, JWT authentication, async queue-based audit logging, and Elasticsearch full-text search.

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Elasticsearch](https://img.shields.io/badge/Elasticsearch-9-005571?style=flat-square&logo=elasticsearch&logoColor=white)](https://www.elastic.co/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![JWT](https://img.shields.io/badge/Auth-JWT-000000?style=flat-square&logo=jsonwebtokens&logoColor=white)](https://jwt.io/)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.0-6BA539?style=flat-square&logo=swagger&logoColor=white)](#interactive-documentation-swagger)
[![Tests](https://img.shields.io/badge/tests-77%20passing-4E9A06?style=flat-square&logo=php&logoColor=white)](#automated-tests)
[![Coverage](https://img.shields.io/badge/coverage-83.7%25-4E9A06?style=flat-square&logo=php&logoColor=white)](#test-coverage)

[Stack](#stack) • [Architecture](#architecture) • [Docker](#docker-setup) • [Local](#local-setup) • [Swagger](#interactive-documentation-swagger) • [Full-text search](#full-text-search-elasticsearch) • [Tests](#automated-tests) • [API examples](#api-call-examples) • [Extras](#implemented-extras)

</div>

---

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel 13 |
| Database | PostgreSQL 16 |
| Search | Elasticsearch 9 (`elasticsearch/elasticsearch`) |
| Authentication | JWT (`tymon/jwt-auth`) |
| Documentation | OpenAPI 3 / Swagger (`darkaonline/l5-swagger`) |
| Infrastructure | Docker Compose — PHP-FPM, Nginx, Postgres, Elasticsearch, queue worker |
| Tests | PHPUnit |

---

## Architecture

```
app/
├── DataTransferObjects/   # ProductFilters, AuthResult — strongly typed instead of loose arrays
├── Enums/                 # ProductLogAction
├── Exceptions/            # ApiExceptionRenderer — standardized JSON response for every error
├── Http/
│   ├── Controllers/Api/V1 # Thin controllers, no business logic
│   ├── Requests/          # Form Requests — all input validation, messages in pt-BR
│   └── Resources/         # API Resources — every data response
├── Jobs/                  # LogProductActivity and SyncProductSearchIndex — async, never synchronous in the controller
├── Models/
├── OpenApi/                # Global Swagger definitions (info, security, shared schemas)
├── Providers/              # Interface → implementation bindings
├── Repositories/           # Data access (Eloquent) isolated behind an interface
└── Services/
    └── Search/               # Elasticsearch integration, isolated behind an interface
```

**Controller → Service → Repository** — each layer depends on an interface (`ProductRepositoryInterface`, `ProductServiceInterface`, `AuthServiceInterface`, `ProductSearchServiceInterface`), with the concrete implementation resolved via the container in `AppServiceProvider`. This keeps business logic testable and swappable (e.g., you can replace the Postgres repository, or the search engine, without touching the controller) without forcing unnecessary patterns on top of it.

Every product write and the enqueuing of both jobs happen in the same transaction. With the `database` queue on the same connection as the product and `after_commit=false` (the default), a failure to enqueue rolls back the whole operation. Swapping the queue for an external service would require a different strategy, such as an outbox, to keep this guarantee.

The jobs run independently, neither one synchronous in the controller:

- `LogProductActivity` — writes the audit entry to `product_logs`. Runs even on deletion, when the product no longer exists in the database by the time the job executes (which is why `product_logs.product_id` has no foreign key: it's a historical record, not a live relationship).
- `SyncProductSearchIndex` — indexes/removes the product in Elasticsearch. If Elasticsearch is down, this job fails and is retried (`--tries=3` on the worker) without affecting the API response or the audit log — the two failures are isolated from each other.

---

## Docker Setup

Prerequisite: Docker and Docker Compose.

```bash
cp .env.example .env
docker compose up -d --build
```

The `app` container installs dependencies (if `vendor/` doesn't exist), generates `APP_KEY`/`JWT_SECRET` (if missing), waits for Postgres to be ready, runs migrations and the seeder, and generates the Swagger docs before starting PHP-FPM. The seeder preserves the existing user and creates the 50 sample products only when the products table is empty. This also works after recreating the volumes; if all products are deleted, the next boot will populate the samples again. Nginx only starts accepting traffic once this setup actually finishes (via healthcheck). In the background, the app also tries to reindex the products in Elasticsearch (see [Full-Text Search](#full-text-search-elasticsearch)) as soon as the cluster becomes available, without blocking the API for it.

The worker also waits for the `app` healthcheck before starting, and uses `restart: unless-stopped` to recover from failures.

Services started:

| Service | Description |
|---|---|
| `app` | PHP-FPM 8.3 |
| `nginx` | Serves the application at `http://localhost:8000` |
| `pgsql` | PostgreSQL 16 |
| `elasticsearch` | Full-text search, at `http://localhost:9200` |
| `queue` | `php artisan queue:work` in a separate container |

If ports `8000`, `5432` or `9200` are already in use on your machine, adjust `APP_FORWARD_PORT`/`DB_FORWARD_PORT`/`ELASTICSEARCH_FORWARD_PORT` in `.env` before starting the containers — no code changes needed.

Useful commands:

```bash
docker compose logs -f app        # follow the app boot / logs
docker compose logs -f queue      # follow the queue worker
docker compose exec app php artisan migrate:fresh --seed   # reset the database
docker compose exec app php artisan products:reindex        # reindex products in Elasticsearch
docker compose down               # stop everything (keeps data volumes)
docker compose down -v            # stop everything and delete data (Postgres + Elasticsearch)
```

---

## Local Setup

Prerequisites: PHP 8.3+, Composer, the `pdo_pgsql` extension, and a reachable PostgreSQL instance (local or remote).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Edit `.env` to point to your Postgres — the only change needed from the file's default is swapping `DB_HOST=pgsql` (the Docker service name) for `DB_HOST=127.0.0.1` (or your database's host). The rest (`DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) already works with a local Postgres created with those same credentials, or adjust them to your environment.

```bash
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

In a second terminal, the queue worker (required for audit logs to be written):

```bash
php artisan queue:work
```

The application runs at `http://localhost:8000`.

Elasticsearch is optional in this mode: without it, the whole API works normally, only the `GET /products/search` endpoint becomes unavailable (returns 503). To enable it locally, run a reachable Elasticsearch 9.x (`ELASTICSEARCH_HOST=127.0.0.1:9200` in `.env`) and run `php artisan products:reindex`.

---

## Interactive Documentation (Swagger)

With the application running (Docker or local):

| | |
|---|---|
| UI | `http://localhost:8000/api/documentation` |
| JSON (OpenAPI 3) | `http://localhost:8000/docs` |

The docs are regenerated automatically on every request (`L5_SWAGGER_GENERATE_ALWAYS=true` in `.env.example`, meant for development). To generate manually:

```bash
php artisan l5-swagger:generate
# or, via Docker:
docker compose exec app php artisan l5-swagger:generate
```

You can authenticate right from the UI: register/log in via `/auth/register` or `/auth/login`, copy the `access_token` from the response, and paste it into the **Authorize** button (`bearerAuth` scheme) to test the product endpoints directly from the browser.

---

## Full-Text Search (Elasticsearch)

`GET /api/v1/products?search=` (documented under [Products](#products)) is the name filter required by the challenge and runs directly against Postgres. `GET /api/v1/products/search` is an **additional** endpoint, dedicated to the smart-search extra powered by Elasticsearch — it doesn't replace the filter above, it complements it:

```bash
curl "http://localhost:8000/api/v1/products/search?q=keyboard&per_page=10" \
  -H "Authorization: Bearer {access_token}"
```

```json
{
  "data": [ /* products, in ProductResource format, ordered by relevance (_score) */ ],
  "meta": { "total": 3 },
  "suggestions": ["mechanical keyboard", "wireless keyboard"]
}
```

What this endpoint does that the Postgres filter doesn't:

- **Relevance** — `multi_match` weighted more heavily on `nome`, then `categoria`, then `descricao`: more relevant results come first.
- **Typo tolerance** — `fuzziness: AUTO`, so small typos like `"keybord"` still match `"Keyboard"`.
- **Suggestions** — a completion suggester over `nome`, useful for autocomplete as the user types (e.g., `q=Key` suggests names starting with "Key", even when the main full-text search returns nothing for such a short prefix).

Indexed documents come from Postgres (the source of truth); Elasticsearch only holds a search-optimized copy, kept in sync asynchronously by the `SyncProductSearchIndex` job on every product create/update/delete — the same pattern as the audit log job, never synchronous in the controller.

Index populated automatically:

- **Docker** — the `app` container runs `products:reindex` in the background after setup (with retry, since Elasticsearch is usually still booting at that point).
- **Local** — run `php artisan products:reindex` manually after `migrate --seed` (see [Local Setup](#local-setup)).

The command also removes from the index any documents whose product no longer exists in PostgreSQL, including when the database is empty. Reconciliation runs in batches and preserves the active index. Concurrent writes keep being synced by the jobs; the operation isn't a transactional snapshot between the two services. On deletion, only 404 is ignored; other Elasticsearch errors allow the worker to retry.

---

## Automated Tests

```bash
php artisan test
```

Runs against SQLite in memory (configured in `phpunit.xml`), without needing Postgres or Elasticsearch available — no dependency on Docker or any external service. In the test environment, `ProductSearchServiceInterface` is bound to a null implementation (`NullProductSearchService`) instead of the real Elasticsearch, for the same reason the tests use SQLite instead of Postgres: testing the application's behavior, not its infrastructure.

Covers authentication (register, login, logout, refresh, including token blacklisting) and product business rules (CRUD, filters, pagination, authorization, and the dispatch of the async audit and indexing jobs).

Includes regression tests for pagination with matching timestamps, PostgreSQL numeric limits, repeated seeding, HTTP error headers, and Elasticsearch reconciliation with simulated HTTP failures. The configuration forces SQLite in memory even with external variables set. The suite aborts before migrations if it detects a different database configuration; if that happens, clear the cache with `php artisan config:clear`.

### Test Coverage

```bash
composer test-coverage
```

Uses the [PCOV](https://github.com/krakjoe/pcov) driver, already enabled in the Docker image (`docker compose exec app composer test-coverage`) and scoped to `app/` only via `pcov.directory`, so it doesn't instrument `vendor/`. Running it locally outside Docker requires installing PCOV or Xdebug separately — plain PHP doesn't ship with one.

Current coverage: **83.7%** of lines in `app/`. The lowest points are infrastructure code that's hard to exercise without the real services: `OpenApi/GeneratorFactory` (integration with L5Swagger's generator, covered indirectly by `l5-swagger:generate` running in Docker, not by a unit test), parts of the scroll-based reconciliation in `ElasticsearchProductSearchService`, and `ReindexProducts` (an infrastructure command, tested via `ElasticsearchProductSearchServiceTest` at the service level, not the command itself).

---

## API Call Examples

Test user created by the seeder: `teste@productapi.com` / `password123`.

### Authentication

#### `POST` `/api/v1/auth/register` — Register

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "password12345",
    "password_confirmation": "password12345"
  }'
```

#### `POST` `/api/v1/auth/login` — Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "teste@productapi.com", "password": "password123"}'
```

Response (register/login/refresh share the same format):

```json
{
  "user": { "id": 1, "name": "Test User", "email": "teste@productapi.com", "created_at": "..." },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

#### `POST` `/api/v1/auth/refresh` — Refresh

Accepts an already-expired token, as long as it's within `refresh_ttl`.

```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Authorization: Bearer {access_token}"
```

#### `POST` `/api/v1/auth/logout` — Logout

Invalidates the current token.

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {access_token}"
```

### Products

All endpoints below require `Authorization: Bearer {access_token}`.

#### `GET` `/api/v1/products` — List with filters and pagination

```bash
curl "http://localhost:8000/api/v1/products?search=mouse&categoria=Peripherals&preco_min=50&preco_max=500&em_estoque=true&per_page=10" \
  -H "Authorization: Bearer {access_token}"
```

| Parameter | Description |
|---|---|
| `search` | Search by name (case-insensitive). For full-text search with relevance and suggestions, see [Full-Text Search](#full-text-search-elasticsearch) |
| `categoria` | Filters by exact category |
| `preco_min` | Minimum price |
| `preco_max` | Maximum price |
| `em_estoque` | `true`/`false` — stock availability |
| `per_page` | Items per page (1–100, default 15) |
| `page` | Current page |

#### `POST` `/api/v1/products` — Create

`preco` accepts values from 0 to 99999999.99; `estoque` accepts integers from 0 to 2147483647. The same limits apply to updates; values outside these ranges return 422.

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Mechanical keyboard",
    "descricao": "Blue switches, ABNT2 layout",
    "preco": 349.90,
    "categoria": "Peripherals",
    "estoque": 20
  }'
```

#### `GET` `/api/v1/products/{id}` — Detail

```bash
curl http://localhost:8000/api/v1/products/1 -H "Authorization: Bearer {access_token}"
```

#### `PUT` `/api/v1/products/{id}` — Update

Accepts partial updates — send only the fields you want to change.

```bash
curl -X PUT http://localhost:8000/api/v1/products/1 \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{"preco": 329.90, "estoque": 15}'
```

#### `DELETE` `/api/v1/products/{id}` — Delete

```bash
curl -X DELETE http://localhost:8000/api/v1/products/1 -H "Authorization: Bearer {access_token}"
```

### Error Format

Every error response follows the same format, regardless of cause (validation, authentication, resource not found, etc.):

```json
{
  "message": "The provided data is invalid.",
  "errors": { "preco": ["The price cannot be negative."] }
}
```

`errors` only appears when the failure is a validation error (422); for every other case (401, 404, 405, 429, 500, 503) the response carries only `message`. The text above is translated for readability — the API actually replies in Portuguese (`pt-BR`), the challenge's target locale.

---

## Implemented Extras

- **Elasticsearch** — full-text search with relevance ranking and suggestions on `GET /products/search`, asynchronous indexing (never synchronous in the controller), and graceful degradation: the whole API keeps working normally if Elasticsearch goes down, only this endpoint returns 503. See [Full-Text Search](#full-text-search-elasticsearch).
- **Full JWT** — register/login/logout/refresh, with token blacklisting (logout and refresh invalidate the previous token) — not just the basic login required by the challenge.
- **Swagger/OpenAPI** — every endpoint documented via `@OA\*` docblock annotations, with request/response schemas, examples, and authentication testable straight from the UI.
- **Repository/Service behind interfaces** — dependency injection via `AppServiceProvider`, without coupling business logic to Eloquent or controllers to the concrete implementation (true for both Postgres and Elasticsearch).
- **Automated tests** — 77 tests (PHPUnit) covering authentication and product business rules, running without depending on Postgres, Elasticsearch, or Docker. 83.7% line coverage in `app/`, measured via PCOV (see [Test Coverage](#test-coverage)).
- **Rate limiting** — 60 requests/minute per user (or per IP, if unauthenticated) on the API routes.
- **Dual execution without code changes** — a single `docker-compose.yml` and `.env.example` cover both Docker and local execution; the `app` container handles its own setup (install, migrate, seed, permissions, docs, reindexing) and Nginx only receives traffic once that setup actually finishes.
- **Portable search** — the Postgres name filter uses `LOWER()+LIKE` instead of `ILIKE` (Postgres-specific), so the same code works in tests (SQLite) and in production (Postgres) without sacrificing case-insensitive search.
