#!/bin/bash
set -e

echo "==> Worker starting"
php -v
php artisan --version

echo "==> Running queue worker"
exec php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90 --verbose
