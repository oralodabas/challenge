FROM php:7.2-fpm

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
    jpegoptim optipng pngquant gifsicle

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installing extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath opcache
RUN docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/
RUN docker-php-ext-install gd

RUN pecl install xdebug-2.6.0 && docker-php-ext-enable xdebug

# Installing composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Setting locales #
RUN echo fr_FR.UTF-8 UTF-8 > /etc/locale.gen && locale-gen

#RUN php bin/console doctrine:migrations:migrate --no-interaction || :

#RUN php bin/console doctrine:fixtures:load --purge-with-truncate || :
# Changing Workdir
WORKDIR /var/www

EXPOSE 9096
