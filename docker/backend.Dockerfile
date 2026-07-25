FROM php:8.2-fpm-alpine

# 系统依赖与 PHP 扩展
RUN apk add --no-cache \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mysqli \
    zip \
    mbstring \
    intl \
    bcmath \
    opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 先复制 composer 文件加速构建
COPY src/backend/composer.json src/backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 复制源码
COPY src/backend/ ./

RUN composer dump-autoload --no-scripts --optimize

EXPOSE 9000

CMD ["php-fpm"]
