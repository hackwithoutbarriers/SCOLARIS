#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${port}/; s#<VirtualHost \\*:80>#<VirtualHost *:${port}>#" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

php artisan optimize:clear
php artisan storage:link --force
php artisan migrate --force
php artisan optimize

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

exec apache2-foreground
