FROM php:5.6-apache

RUN apt-get update -y && apt-get install -y vim curl  \
    libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli pdo pdo_mysql
ADD . /var/www/html
RUN chown www-data:www-data -R /var/www/html && \
    cp /var/www/html/docker-entrypoint.sh /usr/local/bin/ && \
    cp /var/www/html/docker/docker.conf /etc/apache2/conf-available/docker-php.conf && \
    touch uploads.ini && \
    echo file_uploads = On >> uploads.ini && \
    echo memory_limit = 128M >> uploads.ini && \
    echo upload_max_filesize = 100M >> uploads.ini && \
    echo post_max_size = 128M >> uploads.ini && \
    echo max_execution_time = 600 >> uploads.ini && \
    mv uploads.ini /usr/local/etc/php/conf.d/
EXPOSE 80    
VOLUME [ "/var/www/html/config" ]
VOLUME [ "/var/www/html/public/uploads" ]
CMD ["docker-entrypoint.sh"]