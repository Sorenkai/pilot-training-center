# Intermediate build container for front-end resources
FROM docker.io/library/node:22.9.0-alpine as frontend
# Easy to prune intermediary containers
LABEL org.opencontainers.image.source=https://github.com/Sorenkai/pilot-training-center

WORKDIR /app
COPY ./ /app/

RUN npm ci --omit dev && \
    npm run build

# Setup container
FROM docker.io/library/php:8.3.11-apache-bookworm

# Default container port for the apache configuration
EXPOSE 80 443

RUN apt-get update && \
    apt-get install -y git unzip vim nano ca-certificates && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite ssl

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql


COPY . /var/www/html

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY --from=frontend --chown=www-data:www-data /app/public/ /var/www/html/public/

RUN chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache && \
                    mkdir -p /app/storage/app/public/files
