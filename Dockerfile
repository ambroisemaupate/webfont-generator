FROM php:7.2-fpm-stretch
LABEL org.opencontainers.image.authors="Ambroise Maupate <ambroise@rezo-zero.com>"

ENV DEBIAN_FRONTEND=noninteractive
ENV WWW_DATA_UID=1000
ENV WWW_DATA_GID=1000
ENV BUILD_DEPS="build-essential autoconf libtool automake git cmake zlib1g-dev libmcrypt-dev libssl-dev libbz2-dev libicu-dev python3-pip"
ENV RUN_DEPS="brotli fontforge woff-tools nginx zip unzip supervisor bash python3"
ENV PHP_EXTS="zip intl opcache pcntl"

# Debian Stretch and Buster are end-of-life, use archive repositories
RUN printf '%s\n' \
        'deb http://archive.debian.org/debian stretch main' \
        'deb http://archive.debian.org/debian-security stretch/updates main' \
        > /etc/apt/sources.list \
    && printf '%s\n' \
        'deb http://archive.debian.org/debian buster main' \
        'deb http://archive.debian.org/debian-security buster/updates main' \
        > /etc/apt/sources.list.d/buster.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99archive

# Install fontforge and woff-tools
RUN apt-get update \
    && apt-get install --no-install-recommends -y ${BUILD_DEPS} ${RUN_DEPS} \
    && echo_supervisord_conf > /etc/supervisord.conf \
    && docker-php-ext-install $PHP_EXTS \
    && docker-php-ext-enable $PHP_EXTS \
    && which fontforge \
    && which sfnt2woff \
    && which woff2sfnt

# Install https://github.com/wget/ttf2eot
RUN git clone https://github.com/wget/ttf2eot.git \
    && cd ttf2eot \
    && make \
    && chmod a+x ttf2eot \
    && mv ttf2eot /bin/ttf2eot \
    && cd ../

# Install libbrotli
RUN git clone https://github.com/bagder/libbrotli \
    && cd libbrotli \
    && ./autogen.sh \
    && ./configure \
    && make \
    && make install \
    && cd ../

# Google Woff2
RUN git clone https://github.com/google/woff2.git \
    && cd woff2 \
    && mkdir out \
    && cd out \
    && cmake .. \
    && make \
    && make install \
    && mv woff2_compress /usr/bin/woff2_compress \
    && mv woff2_decompress /usr/bin/woff2_decompress \
    && cd ../../

# Python3 font tools for subsetting
RUN pip3 install fonttools \
    && which pyftsubset

# Install composer and put binary into $PATH
# Composer 2.9+ blocks Twig 2 and Symfony 4.4 packages affected by security advisories
RUN curl -sS https://getcomposer.org/installer | php -- --version=2.8.12 \
    && mv composer.phar /usr/local/bin/ \
    && ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

RUN apt-get purge -y --auto-remove ${BUILD_DEPS} \
    && rm -rf ttf2eot libbrotli woff2 \
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
COPY composer.json /var/www/html/
RUN composer install --no-dev --no-plugins --no-scripts --no-autoloader -n

COPY index.php /var/www/html/
COPY src /var/www/html/src
COPY views /var/www/html/views
COPY assets /var/www/html/assets
COPY config.docker.yml /var/www/html/config.yml
RUN composer dump-autoload -o --no-dev \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/bin/sh", "-c", "exec /usr/bin/supervisord -n -c /etc/supervisord.conf"]
