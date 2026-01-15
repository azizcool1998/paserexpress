FROM debian:12

LABEL maintainer="AzizTech <azizgamingsmd@gmail.com>"
ENV DEBIAN_FRONTEND=noninteractive

# -----------------------------
# Update & Install Dependencies
# -----------------------------
RUN apt update && apt upgrade -y && \
    apt install -y \
        curl wget gnupg2 lsb-release ca-certificates software-properties-common \
        nginx \
        mariadb-client \
        git unzip zip \
        php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-cli php8.2-common \
        supervisor && \
    apt clean

# -----------------------------
# Install NodeJS v24
# -----------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - && \
    apt install -y nodejs && \
    npm install -g pnpm

# -----------------------------
# Install Composer
# -----------------------------
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm composer-setup.php

# -----------------------------
# Configure Nginx & PHP
# -----------------------------
RUN mkdir -p /var/www/html
COPY ./src /var/www/html

# Default Virtual Host
RUN rm -f /etc/nginx/sites-enabled/default

COPY docker/nginx.conf /etc/nginx/sites-available/paserexpress.conf

RUN ln -s /etc/nginx/sites-available/paserexpress.conf /etc/nginx/sites-enabled/paserexpress.conf

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# -----------------------------
# Supervisor to run multiple services
# -----------------------------
RUN mkdir -p /etc/supervisor/conf.d

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# -----------------------------
# Expose Ports
# -----------------------------
EXPOSE 80

# -----------------------------
# Start
# -----------------------------
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
