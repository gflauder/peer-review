FROM php:7.4-fpm

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libmcrypt-dev \
    libonig-dev \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install -j$(nproc) iconv mbstring \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install opcache \
    && docker-php-ext-install pdo_sqlite

# Install additional PHP extensions
RUN if ! pecl list | grep -q mcrypt; then \
    pecl install mcrypt-1.0.3 && docker-php-ext-enable mcrypt; \
    fi

RUN apt-get update && apt-get install -y procps

# Install Xdebug compatible with PHP 7.4 if not already installed
RUN if ! pecl list | grep -q xdebug; then \
    pecl install xdebug-2.9.8 && docker-php-ext-enable xdebug; \
    fi

# Copy custom PHP configuration
COPY custom-php.ini /usr/local/etc/php/conf.d/