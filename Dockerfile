FROM php:7.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    autoconf \
    build-essential \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libssl-dev \
    libicu-dev \
    libonig-dev \
    zlib1g-dev \
    libmcrypt-dev \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) iconv mbstring pdo_mysql zip bcmath opcache pdo_sqlite mcrypt


# Set the environment variable
ARG APP_MODE=development

# Install Xdebug compatible with PHP 7.1 if not already installed
RUN if ! pecl list | grep -q xdebug; then pecl install xdebug-2.7.2; fi

# Copy custom PHP configuration based on APP_MODE
COPY php.ini-${APP_MODE} /usr/local/etc/php/conf.d/php.ini

# Set the command to run php-fpm
CMD ["php-fpm"]