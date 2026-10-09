ARG PHP_IMAGE=php:8.5.11-fpm-alpine3.24
FROM ${PHP_IMAGE} AS base

# PHP extensions: runtime libraries stay, headers and compilers are removed in the same layer
# (they were ~500 MB of the production image). OPcache and mbstring are built into PHP 8.5.
RUN apk add --no-cache \
  bash \
  libpq \
  libzip \
  libpng \
  libjpeg-turbo \
  freetype \
  && apk add --no-cache --virtual .build-deps \
  $PHPIZE_DEPS \
  postgresql-dev \
  libzip-dev \
  libpng-dev \
  libjpeg-turbo-dev \
  freetype-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
  pdo_pgsql \
  pgsql \
  zip \
  exif \
  pcntl \
  bcmath \
  gd \
  && pecl install redis-6.3.0 \
  && docker-php-ext-enable redis \
  && apk del .build-deps \
  && rm -rf /tmp/pear

# Set working directory
WORKDIR /var/www/laravel-api

# ============ Composer dependencies for production (Composer stays out of the final image) ============
FROM base AS vendor

COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/composer

# Manifests first so the dependency layer is reused when only application code changes
COPY composer.json composer.lock ./
RUN composer install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --no-scripts \
  --no-autoloader \
  --prefer-dist

COPY . .
RUN composer dump-autoload \
  --no-dev \
  --optimize \
  --classmap-authoritative \
  && php artisan package:discover --ansi

# ============ Production Stage ============
FROM base AS production

# Copy custom PHP configuration
COPY --from=base /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Create user for Laravel
RUN addgroup -g 1000 laravel && \
  adduser -D -u 1000 -G laravel laravel

# Owned by laravel at copy time: a separate chown layer would duplicate the whole tree
COPY --from=vendor --chown=laravel:laravel /var/www/laravel-api /var/www/laravel-api

# Switch to non-root user
USER laravel

# Expose port
EXPOSE 9000

# Laravel caches (config, events, routes, views) are built at start from the environment the
# container runs with, never at build time; dev keeps them off. Inline because the build
# context (laravel-api/) cannot reach a script in docker/.
ENTRYPOINT ["sh", "-c", "php artisan optimize --no-interaction && exec \"$@\"", "--"]
CMD ["php-fpm"]

# ============ Development Stage ============
FROM base AS development

# Install development tools (the entrypoint runs composer install on the bind-mounted source)
COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache \
  curl \
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
  && pecl install xdebug-3.5.0 \
  && docker-php-ext-enable xdebug \
  && apk del .build-deps

# Copy custom PHP development configuration
COPY --from=base /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# Run as the host user's UID so files written to the bind-mounted source (vendor, storage,
# caches) stay owned by the developer, never by root
ARG UID=1000
ARG GID=1000
RUN addgroup -g "${GID}" laravel && \
  adduser -D -u "${UID}" -G laravel laravel

WORKDIR /var/www/laravel-api
USER laravel

# Note: composer.json, composer.lock, and other files will be mounted via volume
# Composer install will be handled by the entrypoint script for flexibility

ENTRYPOINT ["bash", "/usr/local/bin/docker-entrypoint.sh"]
