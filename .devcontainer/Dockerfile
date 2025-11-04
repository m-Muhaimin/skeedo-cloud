FROM mcr.microsoft.com/devcontainers/php:8.2

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    php-zip \
    php-mysql \
    php-gd \
    php-mbstring \
    php-intl \
    php-xml \
    git \
    unzip \
    curl \
    libpq-dev

# Install Composer (global PHP package manager)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Optionally install Node.js if your frontend or build tools require it
# RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
# RUN apt-get install -y nodejs

# (Optional) Install PostgreSQL client if integrating with Supabase
RUN apt-get install -y postgresql-client

# Set working directory
WORKDIR /workspaces/skeedo-cloud

# You can add more RUN, COPY, or ENV commands here as needed for your setup
