# Base image: PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# 1. Install System Dependencies (Python, git, zip, etc.)
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    git \
    unzip \
    libzip-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP Extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli zip

# 3. Install 'uv' (The Python Package Manager)
COPY --from=ghcr.io/astral-sh/uv:latest /uv /usr/local/bin/uv

# 4. Install Tailwind CLI (Standalone)
# We download the linux-x64 binary and make it executable
RUN curl -sLO https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-x64 \
    && chmod +x tailwindcss-linux-x64 \
    && mv tailwindcss-linux-x64 /usr/local/bin/tailwindcss

# 5. Apache Configuration
# Change DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable mod_rewrite for pretty URLs
RUN a2enmod rewrite

# 6. User Permissions (Match Host User)
ARG UID=1000
ARG GID=1000

# Update www-data to match the host user's UID/GID
# We use usermod/groupmod to change the existing user instead of creating a new one
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data

# 7. Python Environment for MCP Builder
# Create a virtual environment and install dependencies
RUN python3 -m venv /opt/builder_venv
ENV PATH="/opt/builder_venv/bin:$PATH"

# Install Python dependencies for the builder
# We assume these are needed for mcp_server.py
RUN uv pip install fastmcp jinja2 mysql-connector-python

# 7. Permissions
# Ensure www-data owns the web root
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

