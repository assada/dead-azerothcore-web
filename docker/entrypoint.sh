#!/bin/sh
set -eu

mkdir -p storage/app/public storage/app/modelviewer/9.2.0 \
    storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/logs bootstrap/cache
chown www-data:www-data storage storage/app storage/app/public \
    storage/app/modelviewer storage/app/modelviewer/9.2.0 \
    storage/framework storage/framework/cache storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

exec "$@"
