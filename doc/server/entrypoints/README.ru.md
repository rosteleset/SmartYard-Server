# Точки входа сервера

В этом разделе описаны **точки входа** — скрипты в корне `server/`, которые работают как HTTP-шлюзы или CLI-утилиты.

## Список точек входа

- [frontend.php](./frontend.ru.md) — шлюз Web UI API (HTTP).
- [mobile.php](./mobile.ru.md) — шлюз Mobile API (HTTP).
- [cli.php](./cli.ru.md) — CLI утилиты (установка, обслуживание, crontab и т.п.).
- [asterisk.php](./asterisk.ru.md) — интеграция с Asterisk (HTTP).
- [internal.php](./internal.ru.md) — шлюз внутреннего API (HTTP).
- [kamailio.php](./kamailio.ru.md) — интеграция с Kamailio (HTTP).
- [wh.php](./wh.ru.md) — шлюз вебхуков (HTTP).
- [ud363.php](./ud363.ru.md) — интеграция UD363 (HTTP).
- [qr.php](./qr.ru.md) — QR endpoint (HTTP).
- [test.php](./test.ru.md) — локальная точка входа для тестирования.

## Примечания

- Точки входа намеренно сделаны **простыми PHP-скриптами**: загрузка конфига, подключения (БД/Redis/…), затем диспетчеризация в `server/api/*` и/или backends.
- `test.php` предназначен для **локального тестирования** и не является production-интерфейсом.

