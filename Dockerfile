FROM php:8.2-cli

# Install dependencies for MySQL and SQLite
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite

WORKDIR /app

COPY . .

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]