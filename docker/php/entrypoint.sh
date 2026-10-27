#!/bin/sh
#
# MANGNO container entrypoint.
#
# Runtime configuration is supplied through environment variables, so any
# Laravel caching must happen at container start-up rather than at build time.
# For every start we refresh the production caches, then hand control to the
# real process (supervisord, which runs nginx + php-fpm + queue worker).

set -e

if [ -f artisan ]; then
    # Fixed mount point: uploaded avatars survive container replacement.
    # Optional picture storage must not prevent authentication/site startup on
    # a host that runs this entrypoint without permission to initialize mounts.
    mkdir -p /var/lib/mangno/avatars 2>/dev/null || true
    chown www-data:www-data /var/lib/mangno/avatars 2>/dev/null || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Cached manifests are written by root; make sure php-fpm (www-data) can
    # read and, when Laravel needs to, rewrite them.
    chown -R www-data:www-data bootstrap/cache storage 2>/dev/null || true
fi

# Railway (and other platforms) inject a dynamic PORT. Nginx must listen on it.
if [ -n "$PORT" ]; then
    sed -i "s/listen 80 default_server;/listen ${PORT} default_server;/g" /etc/nginx/conf.d/default.conf
    sed -i "s/listen \[::\]:80 default_server;/listen [::]:${PORT} default_server;/g" /etc/nginx/conf.d/default.conf
fi

nginx -t

exec "$@"
