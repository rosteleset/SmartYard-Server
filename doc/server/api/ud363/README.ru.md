# API `ud363` (`server/api/ud363/`)

## Назначение

HTTP-обёртка для сценария **slot → загрузка → выдача ссылки на скачивание**, по смыслу как **[XEP-0363: HTTP File Upload](https://xmpp.org/extensions/xep-0363.html)**. Это **не** XML/XMPP IQ в браузере: те же идеи (имя файла, размер, тип, слот, PUT/GET) переносятся в REST-подобные вызовы `/api/ud363/...`.

Реализация endpoint’ов (`upload`, `download`) сейчас **заготовка** (`GET`/`POST` возвращают `true`); комментарии в PHP повторяют термины XEP (**upload slot**, **file part**).

## Связь с XEP-0363

| Идея XEP | Отражение в API (намерение) |
|---------|------------------------------|
| Запрос слота (`filename`, `size`, опционально `content-type`) | `GET /api/ud363/upload` с query `name`, `date`, `type`, `size` (см. `@api` в `upload.php`) |
| Загрузка тела файла на выданный PUT URL | `POST /api/ud363/upload` с телом частей (`slot`, `part`) |
| Получение ссылки на скачивание | `GET /api/ud363/download` |

Подробный конспект протокола (slot, заголовки PUT, ошибки, CORS, безопасность) — в документации **[точки входа `ud363.php`](../../entrypoints/ud363.ru.md)**.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php`. Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию.

Подробнее про ответы API — [базовый класс `api.php`](../api.ru.md).

## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/ud363`) |
|------|----------------------------------------|
| `download.php` | `/download` |
| `upload.php` | `/upload` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).
