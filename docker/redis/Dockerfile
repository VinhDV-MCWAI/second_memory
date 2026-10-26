# Dev Redis with its config baked in (see docker/postgres/Dockerfile, OPS-05).
# Rebuild after editing redis.conf: `make up`.
FROM redis:7.4.11-alpine3.21
COPY redis.conf /usr/local/etc/redis/redis.conf
