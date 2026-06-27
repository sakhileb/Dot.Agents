#!/usr/bin/env bash
# Run the test suite with sqlite in-memory, overriding any conflicting system env vars.
APP_ENV=testing \
DB_CONNECTION=sqlite \
DB_DATABASE=':memory:' \
CACHE_STORE=array \
SESSION_DRIVER=array \
QUEUE_CONNECTION=sync \
php artisan test "$@"
