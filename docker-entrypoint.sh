#!/bin/bash
set -e

# Esperar a que la base de datos esté lista (opcional pero recomendado)
# Podríamos usar un script wait-for-it, pero por simplicidad confiaremos en depends_on y un pequeño delay
sleep 5

# 1. Instalar Symfony Skeleton si no existe (evita sobreescribir si ya está instalado)
if [ ! -f "bin/console" ]; then
    echo "Inicializando proyecto Symfony 7.4..."
    composer create-project symfony/skeleton:^7.4 tmp
    cp -a tmp/. .
    rm -rf tmp
fi

# 2. Instalar dependencias de Composer
echo "Instalando dependencias de Composer..."
composer install --no-interaction --optimize-autoloader

# 3. Validar existencia de claves JWT
if [ ! -f "config/jwt/private.pem" ]; then
    echo "⚠️ ADVERTENCIA: Las claves JWT no existen en config/jwt/. Revisa el README para saber cómo generarlas por primera vez."
fi

# 4. Ejecutar migraciones de base de datos
echo "Ejecutando migraciones..."
# php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
# Como no hemos generado los archivos de migración físicamente en el repo, 
# si doctrine lanza error por falta de migraciones previas o DB no iniciada, continuamos
php bin/console doctrine:schema:update --force || true

# Asignar permisos correctos a carpetas generadas
chown -R www-data:www-data var config/jwt || true

echo "Despliegue continuo finalizado. Iniciando aplicación..."

# Ejecutar el CMD original (php-fpm)
exec "$@"
