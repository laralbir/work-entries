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

### Obtain a token

```bash
curl -X POST http://localhost:8080/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "your-password"}'
```

### Use the token

```bash
curl http://localhost:8080/api/... \
  -H "Authorization: Bearer <your-jwt-token>"
```

## Project Structure

```
work-entries/
├── config/
│   ├── jwt/                    # RSA keys for JWT (not in repo)
│   └── packages/
│       ├── api_platform.yaml   # API Platform configuration
│       ├── doctrine.yaml       # MySQL 8.0 + Doctrine ORM
│       ├── lexik_jwt_authentication.yaml
│       └── security.yaml       # JWT firewalls
├── docker/
│   └── nginx/default.conf      # Nginx configuration
├── migrations/                 # Doctrine migrations
├── src/
│   ├── Entity/                 # Doctrine entities (DDD)
│   ├── Repository/
│   └── Kernel.php
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

## Architecture

This project follows:
- **Hexagonal Architecture** (Ports & Adapters)
- **Domain Driven Design (DDD)**
- **CQRS** — Commands (writes) separated from Queries (reads)
- **Event Driven** — full audit trail of all changes via domain events

## Useful Commands

```bash
# Create a new migration after modifying entities
php bin/console make:migration

# Run pending migrations
php bin/console doctrine:migrations:migrate

# List registered routes
php bin/console debug:router

# Clear cache
php bin/console cache:clear
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md)
