# Changelog

Todos los cambios relevantes de este proyecto están documentados aquí.  
El formato sigue [Keep a Changelog](https://keepachangelog.com/es/1.0.0/) y el proyecto usa [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Added
- Instalación y configuración de **Doctrine ORM** con driver MySQL 8.0 y charset `utf8mb4`
- Instalación y configuración de **LexikJWTAuthenticationBundle** v3 para autenticación JWT
- Instalación y configuración de **API Platform** v4 como capa de API REST
- Configuración de `security.yaml` con firewalls JWT para `/api/login_check` y `/api/**`
- Ruta `api_login_check` en `routes.yaml`
- Variables de entorno para MySQL en `.env` (`DB_DATABASE`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `DB_PORT`, `DATABASE_URL`)
- `docker-entrypoint.sh` mejorado: espera activa a MySQL, genera claves JWT automáticamente y usa `doctrine:migrations:migrate`
- `README.md` con instrucciones completas de instalación y uso

### Changed
- `config/packages/doctrine.yaml`: eliminada configuración PostgreSQL, establecido MySQL 8.0 con `utf8mb4`
- `config/packages/api_platform.yaml`: título actualizado a *Work Entries API*, formatos JSON y JSON-LD habilitados
- `docker-compose.yml`: nombres de contenedores actualizados a `work_entries_*`

---

## [0.1.0] - 2026-06-22

### Added
- Proyecto Symfony 7.4 inicial (skeleton)
- Docker Compose con servicios: `app` (PHP-FPM), `nginx` (Alpine), `db` (MySQL 8.0)
- Dockerfile basado en `php:8.2-fpm-alpine` con extensiones `pdo_mysql`, `zip`
- Sincronización inicial con repositorio GitHub `laralbir/work-entries`
