FROM php:8.3-fpm

# Install extensions and dependencies
RUN apt-get update && apt-get install -y \
    supervisor \
    mariadb-client \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libicu-dev \
    libexif-dev \
    libmagickwand-dev \
    && pecl install imagick redis \
    && docker-php-ext-install intl pdo pdo_mysql zip exif gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-enable imagick redis


# Install Composer (untuk mengelola dependensi PHP)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN ARCH=$(uname -m) && \
    DISTRO="linux-$([ "$ARCH" = "aarch64" ] && echo arm64 || echo x64)" && \
    NODE_VERSION=22.2.0 && \
    curl -fsSL https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-$DISTRO.tar.xz -o node.tar.xz && \
    mkdir -p /usr/local/lib/nodejs && \
    tar -xJf node.tar.xz -C /usr/local/lib/nodejs && \
    rm node.tar.xz && \
    ln -s /usr/local/lib/nodejs/node-v$NODE_VERSION-$DISTRO/bin/node /usr/bin/node && \
    ln -s /usr/local/lib/nodejs/node-v$NODE_VERSION-$DISTRO/bin/npm /usr/bin/npm && \
    ln -s /usr/local/lib/nodejs/node-v$NODE_VERSION-$DISTRO/bin/npx /usr/bin/npx

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy supervisor config
COPY ./builder/railway/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Salin entrypoint
COPY ./builder/railway/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

# Jalankan pakai entrypoint
ENTRYPOINT ["/entrypoint.sh"]