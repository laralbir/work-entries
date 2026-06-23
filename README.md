# Work Entries API

REST API for employee time-tracking, built with Symfony 7.4, API Platform, and JWT authentication.

## Tech Stack

| Technology | Version |
|---|---|
| PHP | 8.2 |
| Symfony | 7.4 |
| API Platform | 4.x |
| MySQL | 8.0 |
| Nginx | Alpine |
| Docker | - |

## Requirements

- Docker & Docker Compose
- Git

## Installation & Setup

### 1. Clone the repository

```bash
git clone git@github.com:laralbir/work-entries.git
cd work-entries
```

### 2. Configure environment variables

Create a `.env.local` with your own values (never commit this file):

```bash
cp .env .env.local
```

Edit `.env.local` and set at least:

```dotenv
APP_SECRET=<generate-with-openssl-rand-hex-32>
DB_DATABASE=work_entries
DB_USER=work_entries
DB_PASSWORD=work_entries123
DB_ROOT_PASSWORD=root123
JWT_PASSPHRASE=<your-secure-passphrase>
```

### 3. Start Docker

```bash
docker compose up -d --build
```

The entrypoint automatically handles:
- Waiting for MySQL to be ready
- Installing Composer dependencies
- Generating JWT keys (if not present)
- Running database migrations

### 4. Access the application

| Service | URL |
|---|---|
| API (Swagger UI) | http://localhost:8080/api/docs |
| JWT Login | `POST http://localhost:8080/api/login_check` |

## JWT Authentication

### Default admin user

A seed admin user is created automatically by the migrations:

| Field | Value |
|---|---|
| Email | `admin@workentries.com` |
| Password | `nimda` |
| Role | `ROLE_ADMIN` |

> **Change this password immediately in any non-development environment.**

### Obtain a token

```bash
curl -X POST http://localhost:8080/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@workentries.com", "password": "nimda"}'
```

### Use the token

```bash
curl http://localhost:8080/api/... \
  -H "Authorization: Bearer <your-jwt-token>"
```

### Revoke a token (logout)

```bash
curl -X POST http://localhost:8080/api/auth/revoke \
  -H "Authorization: Bearer <your-jwt-token>"
```

Returns `204 No Content`. The token is added to a server-side denylist keyed by its unique `jti` claim. Any subsequent request with that token returns `401`, even if it has not yet expired. Each JWT gets a unique `jti` (UUID v7) injected at creation time, so revoking one session does not affect other active sessions for the same user.

## Project Structure

```
work-entries/
├── config/
│   ├── jwt/                         # RSA keys for JWT (not in repo)
│   └── packages/
│       ├── api_platform.yaml
│       ├── doctrine.yaml
│       ├── lexik_jwt_authentication.yaml
│       └── security.yaml
├── docker/
│   └── nginx/default.conf
├── migrations/                      # Doctrine migrations
├── src/
│   ├── ApiInput/                    # Request DTOs (e.g. WorkEntryInput for POST/PUT work-entries)
│   ├── ApiResource/                 # OpenAPI customizations (OpenApiFactory decorator)
│   ├── Application/                 # CQRS — one Command+Handler or Query+Handler per use case; no framework deps
│   │   ├── Auth/Command/
│   │   ├── User/Command/ & Query/
│   │   └── WorkEntry/Command/ & Query/
│   ├── Controller/                  # Non-API-Platform controllers
│   │   └── Auth/                    # (e.g. RevokeTokenController for POST /api/auth/revoke)
│   ├── Domain/                      # Ports: repository interfaces and domain events; zero infrastructure deps
│   │   ├── Auth/Repository/
│   │   ├── User/Repository/ & Event/
│   │   └── WorkEntry/Repository/ & Event/
│   ├── Entity/                      # Doctrine ORM entities; carry #[ApiResource] metadata and serialization groups
│   ├── EventListener/               # Symfony kernel event listeners (e.g. ApiExceptionListener → JSON error responses)
│   ├── Infrastructure/              # Adapters: framework and persistence implementations
│   │   ├── Event/                   # Domain event subscribers (WorkEntryEventListener — audit logging)
│   │   ├── Persistence/             # Doctrine repository implementations (Auth/, User/, WorkEntry/)
│   │   └── Security/                # JWT lifecycle listeners (JwtCreatedListener injects jti; JwtDecodedListener checks denylist)
│   └── State/                       # API Platform Providers (reads) and Processors (writes)
│       ├── User/
│       └── WorkEntry/
├── tests/Api/
├── docker-compose.yml
├── Dockerfile
└── docker-entrypoint.sh
```

## Database Schema

### `users`

| Column | Type | Constraints |
|---|---|---|
| `id` | `BINARY(16)` | PK, UUID v7 |
| `name` | `VARCHAR(255)` | NOT NULL |
| `email` | `VARCHAR(255)` | NOT NULL, UNIQUE |
| `password` | `VARCHAR(255)` | NOT NULL (hashed) |
| `roles` | `JSON` | NOT NULL (e.g. `["ROLE_WORKER"]`) |
| `created_at` | `DATETIME` | NOT NULL |
| `updated_at` | `DATETIME` | NOT NULL |
| `deleted_at` | `DATETIME` | NULL (soft-delete) |

**Indexes:** `UNIQUE (email)`

### `work_entries`

| Column | Type | Constraints |
|---|---|---|
| `id` | `BINARY(16)` | PK, UUID v7 |
| `user_id` | `BINARY(16)` | FK → `users.id` ON DELETE CASCADE |
| `start_date` | `DATETIME` | NOT NULL |
| `end_date` | `DATETIME` | NULL |
| `created_at` | `DATETIME` | NOT NULL |
| `updated_at` | `DATETIME` | NOT NULL |
| `deleted_at` | `DATETIME` | NULL (soft-delete) |

**Indexes:**
- `IDX_F8330BE7A76ED395 (user_id)` — FK lookup, managed automatically by Doctrine
- `IDX_WE_USER_START_DATE (user_id, start_date)` — composite index that covers the primary query pattern: listing or filtering a user's entries by date range without a full table scan

### `revoked_tokens`

| Column | Type | Constraints |
|---|---|---|
| `jti` | `VARCHAR(36)` | PK (JWT ID, UUID v7) |
| `expires_at` | `DATETIME` | NOT NULL |
| `revoked_at` | `DATETIME` | NOT NULL |

**Indexes:** `IDX_RT_EXPIRES_AT (expires_at)` — for efficient cleanup of expired entries.

### Overlap validation

`POST`, `PUT`, and `PATCH /api/work-entries` reject entries that overlap with an existing (non-deleted) entry for the same user. Two intervals `[A, B]` and `[C, D]` overlap when `A < D` and `C < B` (a `null` end date means the interval is open-ended / still running). Returns `422 Unprocessable Entity` with:

```json
{"status": 422, "detail": "Work entry overlaps with an existing entry."}
```

For PUT and PATCH, the entry being updated is excluded from the overlap check so that saving unchanged dates never triggers a false conflict.

Entries belonging to different users are never considered for overlap.

### Index strategy

Two separate indexes coexist on `work_entries.user_id` rather than a single composite one because MySQL can use a composite index prefix to satisfy a foreign key constraint, but Doctrine validates indexes by exact name — not by column prefix. Replacing the FK index with the composite one would cause `doctrine:schema:validate` to report the schema as out of sync. Keeping both gives optimal query performance while staying compatible with Doctrine's schema tooling.

The `UNIQUE` index on `users.email` is the only index needed on that table: it enforces uniqueness at the database level and doubles as the lookup index for authentication queries.

### Why `BINARY(16)` for UUIDs

UUIDs are stored as `BINARY(16)` instead of the more readable `CHAR(36)`:

| | `CHAR(36)` | `BINARY(16)` |
|---|---|---|
| Storage per row | 36 bytes | 16 bytes |
| Index size | larger | ~56% smaller |
| Comparison speed | string comparison | binary comparison (faster) |
| Readability in MySQL | human-readable | requires `BIN_TO_UUID()` |

For a backend API where UUIDs are never read directly from the database console, the performance gains outweigh the readability trade-off. Symfony UID generates UUID v7 values, which are time-ordered and avoid index fragmentation (a common problem with random UUID v4).

## API Reference

### Authentication

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/api/login_check` | Public | Obtain JWT token |
| `POST` | `/api/auth/revoke` | Required | Revoke the current token (logout) |

### Users

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/api/users` | Required | List users — optional `?name=` and `?email=` partial filters, `?page=` / `?itemsPerPage=` pagination |
| `POST` | `/api/users` | Public | Register a new user |
| `GET` | `/api/users/{id}` | Required | Get own profile |
| `PATCH` | `/api/users/{id}` | Required | Partially update own profile |
| `PUT` | `/api/users/{id}` | Required | Fully replace own profile |
| `DELETE` | `/api/users/{id}` | Required | Soft-delete own account |

### Work Entries

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/api/work-entries` | Required | List own entries — optional `?startDate=` / `?endDate=` filters (ISO 8601), `?page=` / `?itemsPerPage=` pagination |
| `POST` | `/api/work-entries` | Required | Create an entry manually |
| `POST` | `/api/work-entries/clock-in` | Required | Clock in (startDate = now, endDate = null) |
| `GET` | `/api/work-entries/{id}` | Required | Get a single entry |
| `PUT` | `/api/work-entries/{id}` | Required | Fully replace an entry |
| `PATCH` | `/api/work-entries/{id}` | Required | Partially update an entry |
| `DELETE` | `/api/work-entries/{id}` | Required | Soft-delete an entry |
| `POST` | `/api/work-entries/{id}/clock-out` | Required | Clock out (endDate = now) |

#### Pagination response format

All collection endpoints return:

```json
{
  "member": [...],
  "totalItems": 42,
  "view": {
    "@id": "/api/work-entries?page=2",
    "first": "/api/work-entries?page=1",
    "last": "/api/work-entries?page=5",
    "previous": "/api/work-entries?page=1",
    "next": "/api/work-entries?page=3"
  }
}
```

Default page size is 20; maximum is 100.

## Architecture

This project follows:
- **Hexagonal Architecture** (Ports & Adapters)
- **Domain Driven Design (DDD)**
- **CQRS** — Commands (writes) separated from Queries (reads)
- **Event Driven** — full audit trail of all changes via domain events

### Hexagonal Architecture

The core idea is that the **Application Core** (domain model + use-case handlers) defines what it needs from the outside world via interfaces — called **ports** — and external systems plug in through **adapters**. The core never depends on HTTP, Doctrine, or any framework concern.

```
┌─────────────────────────────────────────────────────────┐
│                   DRIVING SIDE                          │
│  HTTP requests, tests                                   │
│  (State Processors/Providers, RevokeTokenController)    │
└────────────────────────┬────────────────────────────────┘
                         │  calls
          ┌──────────────▼──────────────┐
          │      APPLICATION CORE       │
          │  src/Application/  (CQRS)   │
          │  src/Domain/       (ports)  │
          │  src/Entity/       (model)  │
          └──────────────┬──────────────┘
                         │  drives
┌────────────────────────▼────────────────────────────────┐
│                   DRIVEN SIDE                           │
│  Doctrine repositories, event listeners, JWT adapters  │
│  (src/Infrastructure/)                                  │
└─────────────────────────────────────────────────────────┘
```

#### The ports

A port is an interface defined inside the core. It expresses a need in domain terms, with no knowledge of how it will be fulfilled.

| Port | Where defined | What it expresses |
|---|---|---|
| `WorkEntryRepositoryInterface` | `Domain/WorkEntry/Repository/` | How to persist and query work entries |
| `UserRepositoryInterface` | `Domain/User/Repository/` | How to persist and query users |
| `RevokedTokenRepositoryInterface` | `Domain/Auth/Repository/` | How to store and check revoked JWTs |
| `LoggerInterface` (PSR-3) | external standard | How to emit log messages |

#### The adapters

An adapter implements a port using a specific technology. Swapping it out requires no changes to the core.

**Driven adapters** (the core calls these):

| Adapter | Port it implements | Technology |
|---|---|---|
| `DoctrineWorkEntryRepository` | `WorkEntryRepositoryInterface` | Doctrine ORM + MySQL |
| `DoctrineUserRepository` | `UserRepositoryInterface` | Doctrine ORM + MySQL |
| `DoctrineRevokedTokenRepository` | `RevokedTokenRepositoryInterface` | Doctrine ORM + MySQL |
| `WorkEntryEventListener` | _(driven by event dispatcher)_ | PSR-3 logger → stderr |
| `JwtDecodedListener` | _(driven by Lexik JWT bundle)_ | Checks `RevokedTokenRepositoryInterface` |
| `JwtCreatedListener` | _(driven by Lexik JWT bundle)_ | Injects `jti` into JWT payload |

**Driving adapters** (these call the core):

| Adapter | Technology | What it drives |
|---|---|---|
| `WorkEntryCreateProcessor` | API Platform `ProcessorInterface` | Calls `CreateWorkEntryHandler` |
| `WorkEntryCollectionProvider` | API Platform `ProviderInterface` | Calls `ListWorkEntriesHandler` |
| `UserItemProvider` | API Platform `ProviderInterface` | Calls `GetUserHandler` |
| _(all other State classes)_ | API Platform | Corresponding Command/Query handlers |
| `RevokeTokenController` | Symfony controller | Calls `RevokeTokenHandler` |
| `tests/Api/*` | PHPUnit + HTTP client | Drives everything through the HTTP port |

#### The dependency rule

The dependency arrow always points **inward**. The core knows nothing about what is outside:

```
State/  ──→  Application/  ──→  Domain/  (zero framework imports)
Infrastructure/  ──→  Domain/             (implements ports)
```

Application handlers never import from `Symfony\Component\HttpFoundation`, `Doctrine\ORM\EntityManager`, or any infrastructure namespace. They only ever see:
- Domain interfaces (`WorkEntryRepositoryInterface`)
- Domain entities (`WorkEntry`, `User`)
- Standard PHP (`\DateTimeImmutable`, `\Throwable`)
- Symfony's event dispatcher and HTTP exception classes *(see pragmatic simplifications below)*

#### How the port binding is declared

```yaml
# config/services.yaml
App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface:
    class: App\Infrastructure\Persistence\WorkEntry\DoctrineWorkEntryRepository

App\Domain\User\Repository\UserRepositoryInterface:
    class: App\Infrastructure\Persistence\User\DoctrineUserRepository

App\Domain\Auth\Repository\RevokedTokenRepositoryInterface:
    class: App\Infrastructure\Persistence\Auth\DoctrineRevokedTokenRepository
```

Three lines. Replacing the database requires only changing these lines and writing a new adapter class.

#### Pragmatic simplifications

- **HTTP exceptions in the Application layer**: handlers throw `NotFoundHttpException`, `UnprocessableEntityHttpException`, etc. from `symfony/http-kernel`. In strict hexagonal, the domain would throw its own exceptions and each driving adapter would map them to status codes. The current approach saves boilerplate at the cost of a direct Symfony dependency inside the core.
- **`EventDispatcherInterface` is Symfony's**: in strict hexagonal, the core would define its own `EventPublisherInterface` port. Using Symfony's interface directly is a common and accepted trade-off in Symfony projects.
- **`src/State/` is not a true port**: it is an adapter namespace, but it couples to API Platform's `ProviderInterface` / `ProcessorInterface` directly rather than to a project-defined port. This is fine while API Platform is the only HTTP adapter.

### Event-Driven system

Every write operation that changes meaningful state dispatches a domain event after the data is persisted. The flow is:

```
Command Handler
  └─ persists entity
  └─ $dispatcher->dispatch(new WorkEntryCreatedEvent($entry))
       └─ WorkEntryEventListener::onCreated()
            └─ $logger->info('WorkEntry created', ['workEntryId' => …, 'userId' => …])
```

**Domain events** (`src/Domain/*/Event/`) are plain PHP objects with no framework dependencies. They carry the entity that changed and nothing else.

**Listeners** (`src/Infrastructure/Event/`) are registered automatically via `#[AsEventListener]`. They are the only code that needs to change when you want to react differently to an event — Command Handlers never need to know what happens downstream.

#### Events currently dispatched

| Event | Dispatched from | Listener |
|---|---|---|
| `WorkEntryCreatedEvent` | `CreateWorkEntryHandler`, `ClockInHandler` | `WorkEntryEventListener::onCreated` |
| `WorkEntryClockedOutEvent` | `ClockOutHandler` | `WorkEntryEventListener::onClockedOut` |
| `WorkEntryDeletedEvent` | `DeleteWorkEntryHandler` | `WorkEntryEventListener::onDeleted` |
| `UserCreatedEvent` | `CreateUserHandler` | _(no listener yet — extension point)_ |

#### Where to see the logs

The application uses Symfony's built-in PSR-3 logger, which writes to **stderr**. In the Docker setup that output is captured by PHP-FPM and forwarded to the container log. To read it:

```bash
docker logs work_entries_app
```

Each event produces one `[info]` line with structured context:

```
NOTICE: PHP message: [info] WorkEntry created {"workEntryId":"018f…","userId":"018e…"}
NOTICE: PHP message: [info] WorkEntry clocked out {"workEntryId":"018f…","userId":"018e…"}
NOTICE: PHP message: [info] WorkEntry deleted {"workEntryId":"018f…","userId":"018e…"}
```

#### Extending the system

To **add a reaction to an existing event** (e.g. send an email when a work entry is created), create a new listener class and annotate it:

```php
#[AsEventListener(event: WorkEntryCreatedEvent::class)]
final class NotifyOnWorkEntryCreated
{
    public function __invoke(WorkEntryCreatedEvent $event): void { … }
}
```

To **persist a structured audit trail** instead of plain log lines, create a `WorkEntryAuditLog` entity and write to it from the listener. The `IDX_RT_EXPIRES_AT` pattern in `revoked_tokens` is a reference for how to add a time-indexed audit table.

To **react to `UserCreatedEvent`**, create a `UserEventListener` in `src/Infrastructure/Event/` — the event is already dispatched, it just has no subscriber yet.

### CQRS

Every operation in the Application layer is either a **Command** (changes state, has side effects) or a **Query** (reads data, no side effects). They never mix.

#### Structure

Each use case is two files: a message object and its handler.

```
src/Application/
├── WorkEntry/
│   ├── Command/
│   │   ├── CreateWorkEntryCommand.php   ← immutable value object (what to do + data)
│   │   ├── CreateWorkEntryHandler.php   ← validates, persists, dispatches event
│   │   ├── UpdateWorkEntryCommand.php
│   │   ├── UpdateWorkEntryHandler.php
│   │   ├── ClockInCommand.php / ClockInHandler.php
│   │   ├── ClockOutCommand.php / ClockOutHandler.php
│   │   └── DeleteWorkEntryCommand.php / DeleteWorkEntryHandler.php
│   └── Query/
│       ├── GetWorkEntryQuery.php        ← immutable value object (what to fetch)
│       ├── GetWorkEntryHandler.php      ← reads from repository, returns entity
│       ├── ListWorkEntriesQuery.php     ← carries filters + pagination params
│       ├── ListWorkEntriesHandler.php   ← returns WorkEntriesPage (items + totalItems)
│       └── WorkEntriesPage.php          ← read-side response wrapper
└── User/ & Auth/  (same pattern)
```

#### How Commands work

A Command is a `final readonly class` with public constructor properties — an immutable description of the intent:

```php
final readonly class CreateWorkEntryCommand
{
    public function __construct(
        public User $user,
        public \DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate = null,
    ) {}
}
```

Its Handler is an invokable class that does exactly one thing — execute that intent:

```php
// State Processor (API layer) builds the command and calls the handler:
($this->handler)(new CreateWorkEntryCommand(user: $user, startDate: $data->startDate, ...));

// Handler: validate → persist → dispatch event → return entity
public function __invoke(CreateWorkEntryCommand $command): WorkEntry
{
    // validate (overlap check)
    // persist via repository
    // dispatch WorkEntryCreatedEvent
    // return the new entity
}
```

#### How Queries work

A Query carries only read parameters. Its Handler calls the repository and returns data — no persistence, no events:

```php
// State Provider (API layer) builds the query and calls the handler:
($this->handler)(new ListWorkEntriesQuery(user: $user, from: $from, to: $to, offset: 0, limit: 20));

// Handler: read → return
public function __invoke(ListWorkEntriesQuery $query): WorkEntriesPage
{
    $items = $this->repository->findByUser(…);
    $total = $this->repository->countByUser(…);
    return new WorkEntriesPage($items, $total);
}
```

#### How the API layer connects to CQRS

API Platform's **State Processors** (write side) and **State Providers** (read side) are the only layer aware of both HTTP and the Application layer. They translate one into the other:

```
HTTP POST /api/work-entries
  → WorkEntryCreateProcessor   (State/WorkEntry/)
      → new CreateWorkEntryCommand(…)
          → CreateWorkEntryHandler   (Application/WorkEntry/Command/)
              → WorkEntryRepositoryInterface   (Domain port)
                  → DoctrineWorkEntryRepository   (Infrastructure adapter)

HTTP GET /api/work-entries
  → WorkEntryCollectionProvider   (State/WorkEntry/)
      → new ListWorkEntriesQuery(…)
          → ListWorkEntriesHandler   (Application/WorkEntry/Query/)
              → WorkEntryRepositoryInterface   (Domain port)
```

#### Design decisions

- **No message bus**: Handlers are invoked directly with `($this->handler)(new Command(…))`. Symfony Messenger is not used — synchronous dispatch is sufficient at this scale and keeps the stack trace simple.
- **Commands return the entity**: Pure CQRS Commands return `void`. Here they return the persisted entity because API Platform needs it to serialize the HTTP response. This is a deliberate pragmatic trade-off.
- **Queries return entities, not DTOs**: Entities carry serialization groups (`#[Groups]`) already, so a separate read model would be redundant at this scale.

### Domain Driven Design

The project organises code around the **business domain**, not around technical layers. The core idea is that the domain model — the entities, their behaviour, and the contracts they need — lives independently of frameworks, databases, and HTTP.

#### Bounded contexts

The domain is split into three contexts, each with its own namespace under `src/Domain/`:

| Context | Responsibility |
|---|---|
| `User` | Account management: registration, authentication identity, soft-delete |
| `WorkEntry` | Time-tracking: clock-in/out, manual entries, overlap rules |
| `Auth` | Security cross-cut: JWT revocation denylist |

Each context owns its repository interface and its domain events. Infrastructure adapters and application handlers map to the same context breakdown.

#### Entities as the domain model

Entities are not anemic data containers. They encapsulate **business behaviour** and enforce **invariants**:

```php
// Business operation as a method, not a raw setter
$workEntry->softDelete();   // sets deletedAt = now()
$workEntry->restore();      // clears deletedAt
$workEntry->isActive();     // deletedAt IS NULL AND endDate IS NULL

$user->softDelete();
$user->eraseCredentials();  // wipes plainPassword from memory after use
```

The constructor enforces required state — a `WorkEntry` cannot exist without a `User` and a `startDate`:

```php
public function __construct(User $user, \DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate = null)
```

#### Ubiquitous language

The code uses business terminology consistently:

| Code | Business meaning |
|---|---|
| `clockIn()` / `clockOut()` | Start / end a work shift |
| `softDelete()` / `restore()` | Archive / unarchive a record |
| `isActive()` | Entry is running (no end date, not deleted) |
| `findOverlapping()` | Repository method named after the business rule it serves |
| `WorkEntry` | The domain term for a time-tracking record |
| `jti` | JWT ID — the token's identity within the Auth context |

#### Repository interfaces as domain ports

The domain defines **what** it needs from persistence — not how. Application handlers depend only on the interface; Doctrine is invisible to them:

```php
// Domain port — zero infrastructure imports
interface WorkEntryRepositoryInterface
{
    public function findById(Uuid $id): ?WorkEntry;
    public function findOverlapping(User $user, \DateTimeImmutable $start, ?\DateTimeImmutable $end, ?Uuid $excludeId = null): array;
    public function save(WorkEntry $workEntry): void;
    public function flush(): void;
    // …
}
```

The binding between port and adapter is declared in `config/services.yaml`:

```yaml
App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface:
    class: App\Infrastructure\Persistence\WorkEntry\DoctrineWorkEntryRepository
```

Swapping the database engine (e.g. to PostgreSQL or an in-memory store for tests) requires changing only this one line and the adapter class.

#### Soft deletes as a domain policy

Records are never hard-deleted. `deletedAt` is a domain concept, not just a database trick — every repository method filters `deletedAt IS NULL` automatically, and the entity exposes `softDelete()` / `restore()` / `isDeleted()` as first-class operations.

#### Pragmatic simplifications

This project uses DDD selectively. Some stricter patterns are intentionally omitted:

- **No value objects**: `email`, dates, and IDs are primitives. A stricter model would wrap them (`EmailAddress`, `DateRange`, `WorkEntryId`). At this scale, the added indirection isn't justified.
- **No pure domain services**: the overlap rule lives in `CreateWorkEntryHandler`, not in a separate `OverlapChecker` domain service. The handler is small enough that extracting it would be ceremony.
- **Entities carry ORM and serialization annotations**: in strict DDD, the domain model is persistence-ignorant. Here, `#[ORM\*]` and `#[Groups]` annotations live on the entity class — the pragmatic Symfony convention, avoiding a separate mapping layer.

## Useful Commands

All commands run inside the application container (`work_entries_app`):

```bash
# Create a new migration after modifying entities
docker exec work_entries_app php bin/console make:migration

# Run pending migrations
docker exec work_entries_app php bin/console doctrine:migrations:migrate

# List registered routes
docker exec work_entries_app php bin/console debug:router

# Clear cache
docker exec work_entries_app php bin/console cache:clear

# Run the test suite
docker exec work_entries_app php bin/phpunit --no-coverage

# Validate that the database schema matches the entity mappings
docker exec work_entries_app php bin/console doctrine:schema:validate
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md)
