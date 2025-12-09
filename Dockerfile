# Используем официальный образ PHP с Apache
FROM php:8.3-apache

# Устанавливаем расширения, необходимые для Doctrine DBAL (MySQL) и другие
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем модуль Apache rewrite, необходимый для Slim
RUN a2enmod rewrite

# Устанавливаем модуль Apache headers, полезный для CORS
RUN a2enmod headers

# Устанавливаем модуль Apache ENV, который может потребоваться
RUN a2enmod env

# Устанавливаем zip, unzip, git и другие необходимые утилиты
# Также устанавливаем libzip-dev, необходимый для расширения zip
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    # --- НОВОЕ: Установка зависимостей для расширения intl ---
    libicu-dev \
    g++ \
    # --- КОНЕЦ НОВОГО ---
    && docker-php-ext-install zip \
    # --- НОВОЕ: Установка расширения intl ---
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    # --- КОНЕЦ НОВОГО ---
    && rm -r /var/lib/apt/lists/*

# Устанавливаем timezone
RUN echo "date.timezone=Europe/Moscow" > /usr/local/etc/php/conf.d/timezone.ini

# Устанавливаем opcache config (опционально)
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Устанавливаем корневую директорию Apache на /var/www/html
WORKDIR /var/www/html

# Копируем composer.json и composer.lock (если есть) внутрь контейнера
COPY composer.json composer.lock* /var/www/html/

# Устанавливаем зависимости PHP через Composer
RUN composer install --no-dev --optimize-autoloader

# Копируем файлы конфигурации Apache (если есть) или используем стандартный
# COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Экспортируем порт 80
EXPOSE 80

# Команда, выполняемая при запуске контейнера
# Apache уже запущен в официальном образе, но мы можем убедиться
CMD ["apache2-foreground"]