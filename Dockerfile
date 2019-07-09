FROM php:7.2-fpm

ARG DB_HOST

# Changing Workdir
WORKDIR /var/www

# Installing dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    mysql-client \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    netcat \
    procps

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installing extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath opcache
RUN docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/
RUN docker-php-ext-install gd

# Installing composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Setting locales #
RUN echo fr_FR.UTF-8 UTF-8 > /etc/locale.gen && locale-gen

# Copy composer files
COPY ./composer.* /var/www/

COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf

# Composer install without script and autoload stages
RUN composer install --no-scripts --no-autoloader

# Copy and give permissions all codes
COPY --chown=www-data:www-data . ./

# Composer installl with script and autoload stages
RUN composer install

RUN php bin/console doctrine:migrations:migrate --no-interaction || :
#docker-compose run app php bin/consdoctrine:migrations:migrate

RUN php bin/console doctrine:fixtures:load --purge-with-truncate || :

RUN php bin/console fos:elastica:populate --no-debug || :

USER www-data

EXPOSE 9000
