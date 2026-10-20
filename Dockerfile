# Imagem usada pelo Railway: PHP 8.3 com a extensão pdo_mysql.
FROM php:8.3-cli
RUN docker-php-ext-install pdo_mysql \
 && printf "upload_max_filesize=20M\npost_max_size=25M\n" > /usr/local/etc/php/conf.d/uploads.ini
WORKDIR /app
COPY . /app
RUN mkdir -p /app/uploads
# O router deixa passar só as páginas do site e os ficheiros estáticos.
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app publicar/router.php"]
