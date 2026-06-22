# Changelog

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and the project uses [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Added
- Entity **`User`** (`src/Entity/User.php`): UUID, name, email, password (hashed), timestamps, and soft-delete. Implements `UserInterface` and `PasswordAuthenticatedUserInterface`.
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

### Changed
- UUID columns changed from `CHAR(36)` to `BINARY(16)`: 56% less storage, faster index comparisons.
- Composite index `IDX_WE_USER_START_DATE (user_id, start_date)` added to `work_entries`: covers the primary query pattern (entries per user filtered/ordered by date) without a full table scan.
- `AGENTS.md` migrated to `CLAUDE.md` (Claude Code format) with project conventions and console commands.
- `config/packages/doctrine.yaml`: removed PostgreSQL configuration, set MySQL 8.0 with `utf8mb4`.
- `config/packages/api_platform.yaml`: title updated to *Work Entries API*, JSON and JSON-LD formats enabled.
- `docker-compose.yml`: container names updated to `work_entries_*`.

---

## [0.1.0] - 2026-06-22

### Added
- Initial Symfony 7.4 skeleton.
- Docker Compose with services: `app` (PHP-FPM), `nginx` (Alpine), `db` (MySQL 8.0).
- Dockerfile based on `php:8.2-fpm-alpine` with `pdo_mysql` and `zip` extensions.
- Initial sync with GitHub repository `laralbir/work-entries`.
