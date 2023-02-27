FROM php:8.2-fpm-alpine3.17 as base

ENV USER=user
ENV GROUP=docker
ARG UID=1000
ARG GID=1000
ENV BASE_DIRECTORY=/var/local/src
ENV EXECUTABLE_DIRECTORY=/var/local/bin
WORKDIR $BASE_DIRECTORY


RUN apk update \
    && apk upgrade \
    && apk --no-cache add --virtual .build-dependency \
        g++ \
        autoconf \
        make \
    && apk add \
        icu-dev \
    && docker-php-ext-install \
        intl \
        pdo_mysql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-dependency \
    && addgroup --gid $GID $GROUP \
    && adduser \
           --disabled-password \
           --gecos "" \
           --ingroup "$GROUP" \
           --uid "$UID" \
           "$USER" \
    && chown -R $USER:$GROUP $BASE_DIRECTORY \
    && mkdir -p $EXECUTABLE_DIRECTORY \
    && chown -R $USER:$GROUP $EXECUTABLE_DIRECTORY

USER $USER

FROM base AS dev-base

USER root
COPY .docker/php/config/php.ini /usr/local/etc/php/

RUN apk add --no-cache \
        bash-completion \
        git \
        vim

USER $USER
RUN mkdir -p $HOME//bin  \
    && curl -s https://getcomposer.org/installer | php -- \
            --install-dir=$EXECUTABLE_DIRECTORY \
            --filename=composer \
    && wget https://get.symfony.com/cli/installer \
        --output-document \
        - | bash \
    && mv $HOME/.symfony5/bin/* $EXECUTABLE_DIRECTORY/ \
    && rm -rf $HOME/.symfony5 $EXECUTABLE_DIRECTORY/.composer

# Create a developer image for working on Service and running unit tests
FROM dev-base AS dev

USER root

RUN apk --no-cache add --virtual .build-dependency \
            g++ \
            autoconf \
            make \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-dependency

USER $USER

# Add Xdebug to dev image in case we want to use it
FROM dev AS dev-xdebug

USER root
COPY .docker/php/config/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/

RUN apk --no-cache add --virtual .build-dependency \
            g++ \
            autoconf \
            make \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-dependency

USER $USER

# Create test image
FROM dev AS test

USER root
COPY .docker/php/config/php.ini /usr/local/etc/php/

RUN apk --no-cache add --virtual .build-dependency \
        g++ \
        autoconf \
        make \
    && apk add --no-cache \
        mysql-client \
    && apk del .build-dependency

USER $USER
COPY . .
RUN /var/local/bin/composer install \
            --no-interaction \
            --prefer-dist \
            --optimize-autoloader \
            --no-scripts \
        && cp phpstan-src.neon.dist phpstan-src.neon \
        && cp phpstan-tests.neon.dist phpstan-tests.neon \
        && cp phpunit.xml.dist phpunit.xml \
        && cp phpcs.xml.dist phpcs.xml.neon \
        && cp infection.json.dist infection.json \
        && cp .docker/php/bin/wait-for-test-dependencies.sh /var/local/bin/wait-for-test-dependencies.sh \
        && chmod -x /var/local/bin/wait-for-test-dependencies.sh \
        && chown -R $USER:$GROUP /var/local/bin