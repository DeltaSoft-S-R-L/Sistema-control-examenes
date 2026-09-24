FROM php:8.2-cli-alpine

WORKDIR /var/www/html

# Instalar paquetes requeridos del sistema y extensiones de PHP para PostgreSQL
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql bcmath mbstring zip

# Copiar ejecutable oficial de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar código del proyecto
COPY . .

# Permisos de escritura para storage y bootstrap/cache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 4000

# Asegurar .env, generar key y levantar servidor artisan
CMD ["sh", "-c", "php -r \"file_exists('.env') || copy('.env.example', '.env');\" && php artisan key:generate --force && php artisan serve --host=0.0.0.0 --port=4000"]
