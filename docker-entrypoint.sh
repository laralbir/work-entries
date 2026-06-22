#!/bin/bash
set -e

# Esperar a que MySQL esté listo antes de continuar
echo "Esperando a que la base de datos esté disponible..."
until php -r "new PDO('mysql:host=db;dbname=${DB_DATABASE}', '${DB_USER}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 2
    echo "  ... esperando MySQL"
done
echo "MySQL disponible."

# 1. Instalar dependencias de Composer
echo "Instalando dependencias de Composer..."
composer install --no-interaction --optimize-autoloader

# 2. Validar existencia de claves JWT
if [ ! -f "config/jwt/private.pem" ]; then
    echo "⚠️  Claves JWT no encontradas. Generando..."
    php bin/console lexik:jwt:generate-keypair --no-interaction
fi

# 3. Ejecutar migraciones
echo "Ejecutando migraciones de base de datos..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# 4. Permisos correctos
chown -R www-data:www-data var config/jwt || true

echo "✅ Despliegue finalizado. Iniciando aplicación..."

# Ejecutar el CMD original (php-fpm)
exec "$@"
