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

# Clean up any existing Xdebug installation and install Xdebug
RUN pecl uninstall -y xdebug || true
RUN if ! pecl list | grep -q xdebug; then pecl install xdebug-2.7.2; fi && docker-php-ext-enable xdebug

# Set the environment variable
ARG APP_MODE=development

# Copy custom PHP configuration files into the image
COPY php.ini-development /usr/local/etc/php/php.ini-development
COPY php.ini-production /usr/local/etc/php/php.ini-production
COPY custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini

# Copy the correct PHP configuration based on APP_MODE
RUN if [ "$APP_MODE" = "development" ]; then \
      cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini; \
 else \
     cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini; \
 fi

# Set the command to run php-fpm
CMD ["php-fpm"]