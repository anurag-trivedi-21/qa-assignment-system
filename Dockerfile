FROM php:8.5-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libsqlite3-dev \
    zip \
    unzip \
    sqlite3

# Install Node.js (for Vite and frontend assets)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel and Filament
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd intl zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# Update Apache DocumentRoot to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . .

# Avoid "dubious ownership" git errors when the repo was copied in as root
RUN git config --global --add safe.directory /var/www/html

# Setup Laravel .env file first
RUN php -r "file_exists('.env') || copy('.env.example', '.env');"

# Ensure all required Laravel storage directories exist
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

# Install dependencies (this may trigger artisan commands)
RUN composer install --no-interaction --optimize-autoloader

# Setup app key
RUN php artisan key:generate

# Install Node dependencies and build assets
RUN npm install
RUN npm run build

# Set appropriate permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chown www-data:www-data /var/www/html/database \
    && chmod 775 /var/www/html/database

EXPOSE 80

CMD ["apache2-foreground"]
