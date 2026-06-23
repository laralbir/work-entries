# Changelog

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and the project uses [Semantic Versioning](https://semver.org/).

---

## [1.1.1] - 2026-06-23

### Removed
- Empty `.gitignore` placeholder files from `src/ApiResource/`, `src/Controller/`, and `src/Entity/` (directories now contain real code).
- Unused `src/Repository/` directory (leftover Symfony skeleton; replaced by `src/Domain/*/Repository/` in the hexagonal architecture).

---

## [1.1.0] - 2026-06-23

### Added
- **`POST /api/auth/revoke` — JWT token revocation**: adds the caller's token to a `revoked_tokens` table (keyed by the token's unique `jti` claim). Subsequent requests with that token return `401`, even before expiry. Each token gets a unique `jti` (UUID v7) injected at creation time. Revoking one token does not affect other active sessions for the same user.
- `RevokedToken` entity + `revoked_tokens` migration; `RevokedTokenRepositoryInterface` port + `DoctrineRevokedTokenRepository` adapter.
- `JwtCreatedListener` — injects `jti` into every new JWT payload.
- `JwtDecodedListener` — rejects tokens whose `jti` is in the revoked list.
- **Name and email filters on `GET /api/users`**: optional `?name=` and `?email=` query params perform case-insensitive partial matching (SQL `LIKE`). Both params are combinable.
- **Pagination on `GET /api/users`**: `?page=` (default 1) and `?itemsPerPage=` (default 20, max 100). Response includes `totalItems`, `member`, and `view` (prev/next links). Same behaviour as `GET /api/work-entries`.
- **Date filter on `GET /api/work-entries`**: optional `?startDate=` and `?endDate=` query params filter entries whose `startDate` falls within the given range (ISO 8601). Returns `400` for unparseable dates.
- **Pagination on `GET /api/work-entries`**: `?page=` (default 1) and `?itemsPerPage=` (default 20, max 100). Response now includes `totalItems`, `member`, and `view` (prev/next links).
- **Overlap validation on `POST /api/work-entries`**: returns `422 Unprocessable Entity` with `{"status":422,"detail":"Work entry overlaps with an existing entry."}` if the requested `[startDate, endDate]` interval intersects any existing (non-deleted) entry for the same user. An open-ended entry (`endDate: null`) overlaps any existing open or overlapping entry. Entries belonging to different users are never considered.
- **Overlap validation on `PUT /api/work-entries/{id}` and `PATCH /api/work-entries/{id}`**: same rule as POST, but the entry being updated is excluded from the check via `$excludeId` (so a PUT/PATCH with unchanged dates does not self-conflict).
- `WorkEntryRepositoryInterface::findOverlapping(User, startDate, endDate, ?excludeId)` — new port method implemented in `DoctrineWorkEntryRepository` via DQL JOIN on `u.id` with explicit `UuidType` binding.
- Tests: 9 overlap + 7 no-overlap cases for POST; 9 overlap + 7 no-overlap cases for PUT (including the self-exclusion case).
- `swagger.yaml`: `422` response on `POST`, `PUT`, and `PATCH /work-entries`; PATCH endpoint documented.

---

## [1.0.1] - 2026-06-23

### Added
- Test `testRegisterUserValidationFailsOnDuplicateEmail`: asserts that registering with an already-used email returns 422 instead of a 500 DB integrity error.
- `ApiExceptionListener`: converts any non-JSON exception on `/api/*` routes to a JSON response, preventing HTML error pages from leaking.
- `CLAUDE.md`: extended with architecture overview, layer responsibilities, data flow diagram, serialization groups, security rules, and testing commands.

### Fixed
- **Swagger UI JWT authorization**: `App\ApiResource\OpenApiFactory` decorator adds `security: [{JWT: []}]` globally to the OpenAPI spec so all endpoints display the lock icon in Swagger UI and send the `Authorization: Bearer` header automatically after clicking "Authorize".
- **Duplicate email returns 422**: added `#[UniqueEntity(fields: ['email'])]` to `User` so the validator catches the constraint before Doctrine throws a SQL integrity violation.
- **User registration field name**: `plainPassword` property is now serialized as `password` via `#[SerializedName('password')]`; the API now accepts `{"password": "..."}` instead of `{"plainPassword": "..."}`.
- **Clock-in/clock-out URI**: changed from `/api/work_entries/clock-in` to `/api/work-entries/clock-in` (and `/{id}/clock-out`) to match the dash-based URL convention enforced by `path_segment_name_generator`.
- **Tests updated**: all `/api/work_entries/*` URLs in `WorkEntryTest` updated to `/api/work-entries/*`; all `POST /api/users` request bodies changed from `plainPassword` to `password`.

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
