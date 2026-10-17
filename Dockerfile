
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build


FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader


COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts


FROM php:8.3-fpm-bookworm AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        supervisor \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        sockets \
    && mkdir -p /var/log/supervisor \
    && rm -rf /var/lib/apt/lists/*


COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-mangno.ini

WORKDIR /var/www/html


FROM php-base AS app


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


COPY --from=vendor /app /var/www/html

COPY --from=assets /app/public/build /var/www/html/public/build


COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf


COPY docker/php/entrypoint.sh /usr/local/bin/mangno-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/mangno-entrypoint \
    && chmod +x /usr/local/bin/mangno-entrypoint


RUN php artisan package:discover --ansi


RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["mangno-entrypoint"]
CMD ["php-fpm"]


FROM nginx:1.27-alpine AS nginx

COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
