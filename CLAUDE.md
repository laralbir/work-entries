# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Project Context

- **Symfony 7.4**, **PHP 8.2**, **MySQL 8.0**, **nginx**. Use modern PHP features: readonly classes, named arguments, enums, `\DateTimeImmutable`.
- 100% backend JSON API. All responses are JSON — never HTML. Use **API Platform 4.x** and **LexikJWTAuthenticationBundle**.
- Apply **SOLID**, **Hexagonal Architecture**, **DDD**, **CQRS**, and **Event-Driven** patterns (see Architecture section).
- All entities use **soft deletes** (`deletedAt` field with `softDelete()`/`restore()` methods). Never hard-delete records.
- IDs are **UUID v7** (`Symfony\Component\Uid\Uuid`) stored as `BINARY(16)`. Use `UuidGenerator` and `UuidType`.
- Whenever an entity changes, create a migration (`make:migration`) and commit it with the code.
- Keep `README.md`, `CHANGELOG.md`, and `swagger.yaml` updated in English with every change. In `CHANGELOG.md`, always use a specific version number (e.g. `## [1.1.0]`) — never use `## [Unreleased]`.
- Never run `git commit` or `git push` without prior confirmation from the user.

# Running the App

All commands run inside the Docker container:

```bash
docker compose up -d --build                              # start services
docker exec work_entries_app php bin/console cache:clear  # clear cache
docker exec work_entries_app php bin/console debug:router # list routes
docker exec work_entries_app php bin/console doctrine:schema:validate
```

The `docker-entrypoint.sh` auto-runs: composer install, JWT key generation, and DB migrations on startup.

# Testing

Tests hit a real MySQL database (`APP_ENV=test`, configured in `.env.test`). No mocking of the DB.

```bash
# Full suite
docker exec work_entries_app php bin/phpunit --no-coverage

# Single test file
docker exec work_entries_app php bin/phpunit tests/Api/WorkEntryTest.php --no-coverage

# Single test method
docker exec work_entries_app php bin/phpunit --filter testClockIn --no-coverage
```

All test classes extend `App\Tests\Api\ApiTestCase` which provides:
- `truncateTables()` — call in `setUp()` to reset state
- `createUser(email, password, name)` — creates and persists a User
- `getToken(email, password)` — POSTs to `/api/login_check` and returns the JWT
- `authHeaders(token)` — returns `['headers' => ['Authorization' => 'Bearer …']]`

# Console Commands

```bash
bin/console make:migration          # after modifying entities
bin/console doctrine:migrations:migrate
bin/console make:entity
bin/console make:command
bin/console make:controller
```

# Architecture

The codebase uses strict layer separation. The data flow for a write operation is:

```
HTTP Request
  → API Platform Operation (defined on Entity via #[ApiResource])
    → State Processor (src/State/)          ← calls Application layer
      → Command + Handler (src/Application/…/Command/)
        → Repository Interface (src/Domain/…/Repository/)  ← port
          → Doctrine Implementation (src/Infrastructure/Persistence/)  ← adapter
        → Dispatch Domain Event (src/Domain/…/Event/)
          → Infrastructure Event Listener (src/Infrastructure/Event/)
```

For reads, State Providers (src/State/) call Query + QueryHandler (src/Application/…/Query/).

## Layer responsibilities

| Directory | Role |
|---|---|
| `src/Entity/` | Doctrine ORM entities; also carry `#[ApiResource]` metadata and serialization groups |
| `src/State/` | API Platform Providers & Processors — translate HTTP context to Application commands/queries |
| `src/ApiInput/` | Input DTOs for operations where the request body differs from the entity (e.g., `WorkEntryInput`) |
| `src/ApiResource/` | API Platform customizations (e.g., `OpenApiFactory` decorator for global JWT security header) |
| `src/Application/` | CQRS handlers — one Command/Query class + one Handler class per use case; no framework dependencies |
| `src/Domain/` | Repository interfaces (ports) and domain events; zero infrastructure dependencies |
| `src/Infrastructure/` | Doctrine repository implementations and domain event listeners |
| `src/EventListener/` | Symfony kernel listeners (e.g., `ApiExceptionListener` converts non-JSON HTTP errors to JSON) |

## Serialization groups

- `User`: `user:read` / `user:write`
- `WorkEntry`: `work_entry:read` / `work_entry:write`
- `plainPassword` uses `#[SerializedName('password')]` and is only required on create (`groups: ['user:create']`); it is never persisted.

## Security

- Login: `POST /api/login_check` with `{"email": "…", "password": "…"}` — returns `{"token": "…"}`
- Public routes: `/api/login_check`, `/api/docs`, `POST /api/users` (self-registration)
- All other `/api` routes require `IS_AUTHENTICATED_FULLY` (JWT Bearer token)
- `WorkEntry` ownership enforced via `security: 'object.getUser() === user'`
- `User` self-edit enforced via `security: 'object === user'`

## Domain events (audit trail)

Domain events are dispatched from Command Handlers using Symfony's EventDispatcher. `WorkEntryEventListener` in `src/Infrastructure/Event/` subscribes to `WorkEntryCreatedEvent`, `WorkEntryClockedOutEvent`, and `WorkEntryDeletedEvent` and logs them. Extend this listener (or add new ones) to persist audit records to a dedicated table.

## Repository interface binding

Repository interfaces are bound to their Doctrine implementations in `config/services.yaml`:
- `UserRepositoryInterface` → `DoctrineUserRepository`
- `WorkEntryRepositoryInterface` → `DoctrineWorkEntryRepository`
