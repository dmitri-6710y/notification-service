#!/bin/sh

echo "Waiting for Postgres..."
while ! nc -z postgres 5432; do
  sleep 1
done
echo "Postgres ready"

echo "Waiting for Redis..."
while ! nc -z redis 6379; do
  sleep 1
done
echo "Redis ready"

echo "Waiting for RabbitMQ..."
while ! nc -z rabbitmq 5672; do
  sleep 1
done
echo "RabbitMQ ready"

php artisan migrate --force

php artisan rabbitmq:setup-queues

echo "Starting PHP-FPM..."
if [ $# -gt 0 ]; then
    exec "$@"
else
    # Иначе запускаем PHP-FPM
    php-fpm
fi
