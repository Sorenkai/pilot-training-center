FROM docker.io/library/node:22.13.1-alpine as frontend

LABEL org.opencontainers.image.source=https://github.com/Sorenkai/pilot-training-center

WORKDIR /app
COPY ./ /app/

RUN npm ci --omit dev && \
    npm run build

# Setup container
FROM docker.io/library/php:8.4.3-apache-bookworm

# Default container port for the apache configuration
EXPOSE 80 443

RUN apt-get update && \
    apt-get install -y git unzip vim nano ca-certificates libpq-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite ssl

# Copy over configs
COPY ./cont/config/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./cont/config/apache.conf /etc/apache2/apache2.conf
COPY ./cont/config/php.ini /usr/local/etc/php/php.ini

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql pdo_pgsql opcache


COPY . /var/www/html

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY --from=frontend --chown=www-data:www-data /app/public/ /var/www/html/public/

RUN chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache && \
                    mkdir -p /var/www/html/storage/app/public/files

COPY ./cont/entry.sh /usr/local/bin/ptc-entrypoint
RUN chmod +x /usr/local/bin/ptc-entrypoint
ENTRYPOINT [ "ptc-entrypoint" ]
CMD ["apache2-foreground"]