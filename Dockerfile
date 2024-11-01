FROM php:7.4-fpm

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
    libsqlite3-dev \
    curl \
    git \
    make \
    wget \
    && rm -rf /var/lib/apt/lists/*  # Clean up apt cache

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install iconv mbstring pdo_mysql zip bcmath opcache pdo_sqlite

# Uninstall Xdebug if it was previously installed
RUN pecl uninstall -y xdebug || true
# Remove existing Xdebug configurations if any
RUN sed -i '/xdebug/d' /usr/local/etc/php/conf.d/*.ini

# Install Xdebug if not already installed
RUN if ! pecl list | grep -q xdebug; then \
      pecl install xdebug-3.0.4; \
    fi \
    && docker-php-ext-enable xdebug

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set the environment variable
ARG APP_MODE=development

# Set the PHP configuration file based on APP_MODE
RUN if [ "$APP_MODE" = "development" ]; then \
      cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini; \
    else \
      cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini; \
    fi

# Copy additional custom PHP configurations
COPY custom-php.ini /usr/local/etc/php/conf.d/

# Set the command to run php-fpm
CMD ["php-fpm"]
