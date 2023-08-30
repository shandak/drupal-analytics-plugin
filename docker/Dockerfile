FROM php:8.2-fpm-alpine3.18
## Utilities
RUN apk add --no-cache --update zip zlib-dev git python3 py3-pip nodejs npm keychain make gcc g++ pkgconfig pixman-dev cairo-dev pango-dev libjpeg-turbo-dev giflib-dev ;
RUN npm install --global yarn

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions \
    zip \
    mysqli \
    intl \
    pdo_mysql \
    gd \
    bz2 \
    imagick \
    @composer \
    bcmath \
    xdebug-3.2.2 \
    mcrypt \
    sodium \
    sockets;

ARG USER_ID
ARG GROUP_ID
RUN if [ ${USER_ID:-0} -ne 0 ] && [ ${GROUP_ID:-0} -ne 0 ]; then \
    deluser www-data &&\
    if getent group www-data ; then delgroup www-data; fi &&\
    addgroup -g ${GROUP_ID} -S www-data; \
    adduser -D -u ${USER_ID} -S -G www-data www-data \
    ;fi

WORKDIR /workspace
