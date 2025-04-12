FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Enable Apache modules
RUN a2enmod rewrite

# Update Apache configuration
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create Apache configuration directory if it doesn't exist
RUN mkdir -p /etc/apache2/sites-available

# Configure Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["/usr/local/bin/apache2-foreground"] 