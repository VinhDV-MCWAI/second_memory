FROM php:8.3-fpm-alpine AS base

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
  postgresql-dev \
  libzip-dev \
  libpng-dev \
  libjpeg-turbo-dev \
  freetype-dev \
  oniguruma-dev \
  curl \
  bash \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
  pdo_pgsql \
  pgsql \
  zip \
  exif \
  pcntl \
  bcmath \
  gd \
  opcache

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps \
  autoconf \
  gcc \
  g++ \
  make \
  && pecl install redis-6.0.2 \
  && docker-php-ext-enable redis \
  && apk del .build-deps

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/laravel-api

# ============ Production Stage ============
FROM base AS production

# Copy custom PHP configuration
COPY --from=base /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Create user for Laravel
RUN addgroup -g 1000 laravel && \
  adduser -D -u 1000 -G laravel laravel

# Copy application files
COPY --chown=laravel:laravel . .

# Install dependencies with optimizations
RUN composer install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --no-scripts \
  --prefer-dist \
  --optimize-autoloader \
  --classmap-authoritative

# Set permissions
RUN chown -R laravel:laravel /var/www/laravel-api \
  && chmod -R 755 /var/www/laravel-api/storage \
  && chmod -R 755 /var/www/laravel-api/bootstrap/cache

# Switch to non-root user
USER laravel

# Expose port
EXPOSE 9000

CMD ["php-fpm"]

# ============ Development Stage ============
FROM base AS development

# Install development tools
RUN apk add --no-cache \
  git \
  vim \
  postgresql-client

# Install Xdebug for development  
RUN apk add --no-cache --virtual .build-deps \
  autoconf \
  gcc \
  g++ \
  make \
  linux-headers \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && apk del .build-deps

# Copy custom PHP development configuration
COPY --from=base /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

WORKDIR /var/www/laravel-api

# Note: composer.json, composer.lock, and other files will be mounted via volume
# Composer install will be handled by the entrypoint script for flexibility

ENTRYPOINT ["bash", "/usr/local/bin/docker-entrypoint.sh"]
