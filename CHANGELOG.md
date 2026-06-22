# Changelog

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and the project uses [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Fixed
- **Swagger UI JWT authorization**: `App\ApiResource\OpenApiFactory` decorator adds `security: [{JWT: []}]` globally to the OpenAPI spec so all endpoints display the lock icon in Swagger UI and send the `Authorization: Bearer` header automatically after clicking "Authorize".

---

## [1.0.0] - 2026-06-22

### Added

#### API & Architecture
- **User CRUD** via API Platform: `GET /api/users`, `GET /api/users/{id}`, `POST /api/users` (public registration), `PUT /PATCH /api/users/{id}` (own profile only), `DELETE /api/users/{id}` (soft-delete, own only).
- **WorkEntry CRUD** via API Platform: `GET /api/work_entries`, `GET /api/work_entries/{id}`, `POST /api/work_entries`, `PUT/PATCH /api/work_entries/{id}`, `DELETE /api/work_entries/{id}` (soft-delete, own only). Collection filtered to the authenticated user, ordered by `startDate DESC`.
- **Clock-in**: `POST /api/work_entries/clock-in` — creates a new entry with `startDate = now()`.
- **Clock-out**: `POST /api/work_entries/{id}/clock-out` — sets `endDate = now()`; returns 422 if already clocked out.
- JWT security on all endpoints; `POST /api/users` is the only public route.
- **Swagger UI** enabled at `/api/docs` (`symfony/twig-bundle` + `symfony/asset` installed).

#### Architecture layers (Hexagonal / DDD / CQRS / Event-Driven)
- Domain repository interfaces: `UserRepositoryInterface`, `WorkEntryRepositoryInterface`.
- Domain events: `UserCreatedEvent`, `WorkEntryCreatedEvent`, `WorkEntryClockedOutEvent`, `WorkEntryDeletedEvent`.
- Application layer: explicit Command and Query handlers for User and WorkEntry (no Symfony Messenger).
- Infrastructure: `DoctrineUserRepository`, `DoctrineWorkEntryRepository` (always filter `deletedAt IS NULL`).
- State layer: API Platform Providers and Processors for all User and WorkEntry operations.
- `WorkEntryEventListener` logs all domain events via `LoggerInterface`.
- `WorkEntryInput` DTO decouples POST deserialization from the `WorkEntry` constructor.

#### Roles & seed data
- `roles` column (`JSON NOT NULL`) added to `users` table.
- New users default to `["ROLE_WORKER"]`; `getRoles()` always appends `ROLE_USER`.
- Migration **`Version20260622211225`**: adds `roles` column, backfills existing users to `ROLE_WORKER`, and seeds the initial admin user (`admin@workentries.com` / `nimda`, role `ROLE_ADMIN`).

#### Tests
- PHPUnit 11 test suite: 25 tests, 43 assertions covering auth, User CRUD, and WorkEntry CRUD including clock-in/clock-out.
- `tests/Api/ApiTestCase.php` base class with kernel management, table truncation, user creation, and token helpers.
- `phpunit.dist.xml` configured for Docker environments (overrides `APP_ENV`, `KERNEL_CLASS`; uses `dbname_suffix: _test` for test DB isolation).

### Changed
- UUID columns changed from `CHAR(36)` to `BINARY(16)`: 56% less storage, faster index comparisons.
- Composite index `IDX_WE_USER_START_DATE (user_id, start_date)` added to `work_entries`: covers the primary query pattern (entries per user filtered/ordered by date) without a full table scan.
- `AGENTS.md` migrated to `CLAUDE.md` (Claude Code format) with project conventions and console commands.
- `config/packages/doctrine.yaml`: removed PostgreSQL configuration, set MySQL 8.0 with `utf8mb4`.
- `config/packages/api_platform.yaml`: title updated to *Work Entries API*, JSON and JSON-LD formats enabled, `enable_swagger_ui: true`.
- `docker-compose.yml`: container names updated to `work_entries_*`.
- `README.md`: added Docker exec examples for all useful commands, default admin credentials, `roles` column in schema, and Swagger UI URL.

### Added (initial schema)
- Entity **`User`** (`src/Entity/User.php`): UUID, name, email, password (hashed), roles (JSON), timestamps, and soft-delete. Implements `UserInterface` and `PasswordAuthenticatedUserInterface`.
- Entity **`WorkEntry`** (`src/Entity/WorkEntry.php`): UUID, ManyToOne relation to `User`, `startDate`, `endDate` (nullable), timestamps, and soft-delete.
- Migration **`Version20260622174800`**: creates `users` and `work_entries` tables with UUID primary keys in `BINARY(16)`, FK with `ON DELETE CASCADE`, `utf8mb4` charset, and optimised indexes.
- **`swagger.yaml`**: OpenAPI 3.0 spec with `User` and `WorkEntry` schemas, CRUD endpoints, and JWT authentication (`bearerAuth`).
- `symfony/uid` for native UUID v7 support.
- `symfony/maker-bundle` (dev) for scaffolding.
- **Doctrine ORM** installed and configured with MySQL 8.0 driver and `utf8mb4` charset.
- **LexikJWTAuthenticationBundle** v3 installed and configured for JWT authentication.
- **API Platform** v4 installed and configured as the REST API layer.
- `security.yaml` configured with JWT firewalls for `/api/login_check` and `/api/**`.
- Route `api_login_check` added to `routes.yaml`.
- MySQL environment variables in `.env` (`DB_DATABASE`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `DB_PORT`, `DATABASE_URL`).
- `docker-entrypoint.sh`: waits for MySQL readiness, auto-generates JWT keys, and runs `doctrine:migrations:migrate` on every deploy.
- `README.md` with full installation, architecture, schema, and usage documentation.

---

## [0.1.0] - 2026-06-22

### Added
- Initial Symfony 7.4 skeleton.
- Docker Compose with services: `app` (PHP-FPM), `nginx` (Alpine), `db` (MySQL 8.0).
- Dockerfile based on `php:8.2-fpm-alpine` with `pdo_mysql` and `zip` extensions.
- Initial sync with GitHub repository `laralbir/work-entries`.
