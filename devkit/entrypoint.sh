#!/bin/sh
set -eu
cd /app
mkdir -p storage/dev
if [ ! -f storage/dev/app.key ]; then
  php -r 'file_put_contents("storage/dev/app.key",base64_encode(random_bytes(32)),LOCK_EX);chmod("storage/dev/app.key",0600);'
fi
APP_KEY="$(cat storage/dev/app.key)"
export APP_KEY
if [ ! -f vendor/autoload.php ] || [ "${ELANG_DEV_SETUP:-false}" = true ]; then composer install --no-interaction --prefer-dist; fi
if [ "${ELANG_DEV_SETUP:-false}" = true ]; then
  php devkit/prepare-s3.php
  php elang migrate
  php elang platform:setup
fi
exec "$@"
