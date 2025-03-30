#!/bin/bash
set -e

PILOT_TRAINING_CENTER_ROOT=/var/www/html
SELF_SIGNED_KEY=/etc/ssl/private/apache-selfsigned.key
SELF_SIGNED_CERT=/etc/ssl/certs/apache-selfsigned.crt

if [ ! -f "$SELF_SIGNED_KEY" ] || [ ! -f "$SELF_SIGNED_CERT" ]; then
    # Generate a self-signed cert to support SSL connections
    openssl req -x509 -nodes -days 358000 -newkey rsa:2048 -keyout "$SELF_SIGNED_KEY" -out "$SELF_SIGNED_CERT" -subj "/O=Your vACC/CN=Control Center"
fi

if [ -z "$APP_KEY" ] && [ ! -f "$PILOT_TRAINING_CENTER_ROOT/.env" ]; then
    echo "################################################################################"
    echo "WARNING: You need to follow the configuration guide for Pilot Training Center"
    echo "################################################################################"
    echo "WARNING: Copying over example .env file"
    cp .env.example .env
    echo "WARNING: Temporarily updating the application key"
    php artisan key:generate
fi

exec docker-php-entrypoint "$@"