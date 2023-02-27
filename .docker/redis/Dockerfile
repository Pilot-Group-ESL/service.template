FROM redis:7.0-alpine3.17

COPY .docker/redis/config/redis.conf /usr/local/etc/redis/redis.conf
CMD [ "redis-server", "/usr/local/etc/redis/redis.conf" ]