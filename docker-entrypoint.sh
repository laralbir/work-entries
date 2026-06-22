#!/bin/bash
set -e

# La base de datos ya está disponible gracias al healthcheck de docker-compose
echo "✅ MySQL disponible (garantizado por healthcheck)."

# 1. Instalar dependencias de Composer (si vendor no existe o está incompleto)
echo "📦 Instalando dependencias de Composer..."
composer install --no-interaction --optimize-autoloader

# 2. Generar claves JWT si no existen
if [ ! -f "config/jwt/private.pem" ]; then
    echo "🔑 Generando claves JWT..."
    php bin/console lexik:jwt:generate-keypair --no-interaction
fi

# 3. Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# 4. Permisos
chown -R www-data:www-data var config/jwt 2>/dev/null || true

echo "🚀 Despliegue finalizado. Iniciando aplicación..."
exec "$@"
