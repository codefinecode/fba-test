FROM php:8.2-cli

WORKDIR /app
COPY . /app

RUN apt-get update && apt-get install -y zip unzip git libzip-dev             && docker-php-ext-install zip

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"             && php composer-setup.php --install-dir=/usr/local/bin --filename=composer             && rm composer-setup.php

RUN composer install --no-interaction --prefer-dist

CMD ["vendor/bin/phpunit", "--colors=always"]
