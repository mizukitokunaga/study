FROM php:8.2-apache

# PostgreSQL ドライバを含む PDO をインストール
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# アプリケーションコードをコピー
COPY . /var/www/html

# オーナーを設定（必要に応じて）
RUN chown -R www-data:www-data /var/www/html
