# ─────────────────────────────────────────────────────────────────
# LMSAdvisor — Production Dockerfile
# PHP 8.2-FPM + Apache
# ─────────────────────────────────────────────────────────────────
FROM php:8.2-apache

LABEL maintainer="LMSAdvisor <dev@lmsadvisor.com>"
LABEL description="LMSAdvisor LMS — PHP 8.2 + MariaDB"

# ── System dependencies ────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libcurl4-openssl-dev \
    default-mysql-client cron curl git \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ─────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql mysqli mbstring zip gd curl xml opcache

# ── Apache config ──────────────────────────────────────────────────
RUN a2enmod rewrite headers expires deflate

COPY docker/apache/lmsadvisor.conf /etc/apache2/sites-available/000-default.conf

# ── PHP config ─────────────────────────────────────────────────────
COPY docker/php/php.ini    /usr/local/etc/php/conf.d/lmsadvisor.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# ── App ────────────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# Storage dirs writable by www-data
RUN mkdir -p storage/uploads storage/cache storage/logs \
 && chown -R www-data:www-data storage \
 && chmod -R 755 storage

# Cron for queue processing + analytics purge
COPY docker/cron/lmsadvisor-cron /etc/cron.d/lmsadvisor
RUN chmod 0644 /etc/cron.d/lmsadvisor && crontab /etc/cron.d/lmsadvisor

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
