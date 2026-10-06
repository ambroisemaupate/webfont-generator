FROM php:8.5-fpm-trixie
LABEL org.opencontainers.image.authors="Ambroise Maupate <ambroise@rezo-zero.com>"

ENV DEBIAN_FRONTEND=noninteractive
ENV WWW_DATA_UID=1000
ENV WWW_DATA_GID=1000
ENV BUILD_DEPS="build-essential git libicu-dev libzip-dev"
ENV RUN_DEPS="fontforge woff-tools woff2 fonttools nginx zip unzip supervisor libicu76 libzip5"
ENV PHP_EXTS="zip intl"

# Font tools: fontforge, sfnt2woff/woff2sfnt, woff2_compress/woff2_decompress, pyftsubset
RUN apt-get update \
    && apt-get install --no-install-recommends -y ${BUILD_DEPS} ${RUN_DEPS} \
    && docker-php-ext-install $PHP_EXTS \
    && which fontforge sfnt2woff woff2sfnt woff2_compress woff2_decompress pyftsubset

# Install https://github.com/wget/ttf2eot
RUN git clone --depth 1 https://github.com/wget/ttf2eot.git \
    && make -C ttf2eot \
    && mv ttf2eot/ttf2eot /bin/ttf2eot \
    && rm -rf ttf2eot

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get purge -y --auto-remove ${BUILD_DEPS} \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && usermod -u ${WWW_DATA_UID} www-data \
    && groupmod -g ${WWW_DATA_GID} www-data

COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/nginx /etc/nginx
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/supervisor/before_launch.ini /etc/supervisor/conf.d/00_before_launch.conf
COPY docker/supervisor/services.ini /etc/supervisor/conf.d/01_services.conf
COPY docker/before_launch.sh /before_launch.sh

WORKDIR /var/www/html

# Install dependencies first to benefit from Docker cache
RUN rm -rf /var/www/html/*
COPY composer.json composer.lock /var/www/html/
RUN composer install --no-dev --no-plugins --no-scripts --no-autoloader -n

COPY index.php /var/www/html/
COPY src /var/www/html/src
COPY views /var/www/html/views
COPY assets /var/www/html/assets
COPY config.yml /var/www/html/
RUN composer dump-autoload -o --no-dev \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/bin/sh", "-c", "exec /usr/bin/supervisord -n -c /etc/supervisord.conf"]
