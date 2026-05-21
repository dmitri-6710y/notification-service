

## Поднять проект

```bash
cp .env.example .env
docker-compose up -d --build
```

## Тестирование

```bash
docker-compose exec app php artisan test
```

## API

### Отправка уведомлений
POST `/api/notifications/send`

Пример запроса:
```bash
curl -X POST http://localhost:8080/api/notifications/send \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: my-unique-key-123" \
  -d '{
    "message": "Ваш код: 1234",
    "recipients": ["+79991234567", "user@example.com"],
    "channel": "sms",
    "priority": "high"
  }'
```

Ответ:
```bash
{
  "message": "Notifications queued",
  "notifications": [
    {
      "id": 1,
      "uuid": "abc-123",
      "recipient_id": "+79991234567",
      "status": "pending"
    }
  ]
}
```

### Получение статуса уведомлений получателя
GET `/api/notifications/status?recipient_id=+79991234567`

Пример запроса:
```bash
curl "http://localhost:8080/api/notifications/status?recipient_id=+79991234567"
```

Ответ:
```bash
{
  "recipient_id": "+79991234567",
  "notifications": [
    {
      "uuid": "abc-123",
      "channel": "sms",
      "message": "Ваш код: 1234",
      "priority": "high",
      "status": "delivered",
      "failure_reason": null,
      "retry_count": 0,
      "created_at": "2026-05-21 20:37:57"
    }
  ],
  "total": 1
}
```

## Документация OpenAPI (Swagger)
Файл спецификации: [openapi.yaml](openapi.yaml)


## Использованные технологии
- Фреймворк: Laravel 11
- БД: PostgreSQL
- Брокер сообщений: RabbitMQ
- Хранение ключей идемпотентности: Redis
- Контейнеризация: Docker Compose
- Автотесты: PHPUnit

## Идемпотентность
Заголовок Idempotency-Key позволяет защититься от дублирования. При повторной отправке с тем же ключом сервер вернёт ранее созданные уведомления (код 200), не выполняя повторную рассылку.

