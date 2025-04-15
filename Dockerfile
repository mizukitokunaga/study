FROM php:8.2-apache

# PostgreSQL ドライバをインストール
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql

# 必要なら mod_rewrite も有効化
RUN a2enmod rewrite

# アプリケーションコードをコピー
COPY . /var/www/html

# パーミッション調整（任意）
RUN chown -R www-data:www-data /var/www/html

