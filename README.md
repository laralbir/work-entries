# Work Entries API

API REST para la gestión de fichajes de empleados, construida con Symfony 7.4, API Platform y autenticación JWT.

## Stack Tecnológico

| Tecnología | Versión |
|---|---|
| PHP | 8.2 |
| Symfony | 7.4 |
| API Platform | 4.x |
| MySQL | 8.0 |
| Nginx | Alpine |
| Docker | - |

## Requisitos

- Docker & Docker Compose
- Git

## Instalación y Arranque

### 1. Clonar el repositorio

```bash
git clone git@github.com:laralbir/work-entries.git
cd work-entries
```

### 2. Configurar variables de entorno

Crea un `.env.local` con tus valores específicos (nunca subas este fichero al repo):

```bash
cp .env .env.local
```

Edita `.env.local` y ajusta al menos:

```dotenv
APP_SECRET=<genera-uno-con-openssl-rand-hex-32>
DB_DATABASE=work_entries
DB_USER=work_entries
DB_PASSWORD=work_entries123
DB_ROOT_PASSWORD=root123
JWT_PASSPHRASE=<tu-passphrase-segura>
```

### 3. Levantar Docker

```bash
docker compose up -d --build
```

El entrypoint se encargará automáticamente de:
- Esperar a que MySQL esté disponible
- Instalar dependencias de Composer
- Generar las claves JWT (si no existen)
- Ejecutar migraciones de base de datos

### 4. Acceder a la aplicación

| Servicio | URL |
|---|---|
| API (Swagger UI) | http://localhost:8080/api/docs |
| Login JWT | `POST http://localhost:8080/api/login_check` |

## Autenticación JWT

### Obtener un token

```bash
curl -X POST http://localhost:8080/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "tu-password"}'
```

### Usar el token

```bash
curl http://localhost:8080/api/... \
  -H "Authorization: Bearer <tu-jwt-token>"
```

## Estructura del Proyecto

```
work-entries/
├── config/
│   ├── jwt/                    # Claves RSA para JWT (no en repo)
│   └── packages/
│       ├── api_platform.yaml   # Configuración API Platform
│       ├── doctrine.yaml       # MySQL 8.0 + Doctrine ORM
│       ├── lexik_jwt_authentication.yaml
│       └── security.yaml       # Firewalls JWT
├── docker/
│   └── nginx/default.conf      # Configuración Nginx
├── migrations/                 # Migraciones Doctrine
├── src/
│   ├── Entity/                 # Entidades Doctrine (DDD)
│   ├── Repository/
│   └── Kernel.php
├── docker-compose.yml
├── Dockerfile
└── docker-entrypoint.sh
```

## Arquitectura

El proyecto sigue los principios de:
- **Arquitectura Hexagonal** (Ports & Adapters)
- **Domain Driven Design (DDD)**
- **CQRS** – separación de Commands (escritura) y Queries (lectura)
- **Event Driven** – registro de todos los cambios mediante eventos de dominio

## Comandos Útiles

```bash
# Crear una nueva migración tras modificar entidades
php bin/console make:migration

# Ejecutar migraciones pendientes
php bin/console doctrine:migrations:migrate

# Ver rutas registradas
php bin/console debug:router

# Limpiar caché
php bin/console cache:clear
```

## CHANGELOG

Ver [CHANGELOG.md](CHANGELOG.md)
