FROM nginx:latest

COPY ./docker/nginx/default.conf /etc/nginx/

ARG PHP_UPSTREAM_CONTAINER=php-fpm
ARG PHP_UPSTREAM_PORT=9000

# RUN echo "upstream php-upstream { server ${PHP_UPSTREAM_CONTAINER}:${PHP_UPSTREAM_PORT}; }" > /etc/nginx/conf.d/upstream.conf \
#     && rm /etc/nginx/conf.d/default.conf

# COPY ./docker/nginx/default.conf.template /etc/nginx/templates/