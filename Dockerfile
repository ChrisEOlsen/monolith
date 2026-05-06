FROM php:8.2-fpm

WORKDIR /var/www/html

# 1. System dependencies
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    git \
    unzip \
    libzip-dev \
    default-mysql-client \
&& rm -rf /var/lib/apt/lists/*

# 2. PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli zip

# 3. phpredis
RUN pecl install redis && docker-php-ext-enable redis

# OPcache is enabled by default in php:8.2-fpm. opcache.ini config is COPY'd in Task 2.

# 5. Tailwind CLI
RUN curl -sLO https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-x64 \
    && chmod +x tailwindcss-linux-x64 \
    && mv tailwindcss-linux-x64 /usr/local/bin/tailwindcss

# 6. uv (Python package manager)
COPY --from=ghcr.io/astral-sh/uv:latest /uv /usr/local/bin/uv

# 7. User permissions (match host UID/GID)
ARG UID=1000
ARG GID=1000
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data

# 8. Python environment for MCP builder
RUN python3 -m venv /opt/builder_venv
ENV PATH="/opt/builder_venv/bin:$PATH"
RUN uv pip install fastmcp jinja2 mysql-connector-python

# 9. Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
