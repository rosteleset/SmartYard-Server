# `server/services/sys_exporter` — Prometheus exporter

Экспортёр метрик для Prometheus (мониторинг IP-домофонии).

## Установка и запуск

1. Создать `.env` из примера:

```shell
cp .env_example .env
```

2. Зависимости:

```shell
npm i
```

3. Запуск:

```shell
npm start
```

## Разработка

Запуск в Docker:

```shell
docker compose up -d
```

Код: [`server/services/sys_exporter/`](../../../server/services/sys_exporter/).
