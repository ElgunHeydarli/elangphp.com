FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends git unzip python3 libpq-dev libzip-dev libonig-dev libxml2-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libcurl4-openssl-dev curl \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j2 pdo_mysql pdo_pgsql mbstring dom zip gd pcntl posix curl \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY devkit/entrypoint.sh /usr/local/bin/elang-dev
RUN chmod +x /usr/local/bin/elang-dev
ENTRYPOINT ["elang-dev"]
CMD ["php", "elang", "serve", "--host=0.0.0.0", "--port=8000"]
