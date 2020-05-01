FROM prooph/php:7.4-fpm

# Add php-ext-amqp
RUN apk add --no-cache --no-progress --virtual BUILD_DEPS_PHP_AMQP rabbitmq-c-dev \
    && apk add --no-cache --no-progress rabbitmq-c \
    && apk add --no-cache git autoconf automake gawk build-base \
    && pecl install amqp \
    && docker-php-ext-enable amqp
