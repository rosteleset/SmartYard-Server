# Утилиты (`server/utils/`)

Общие процедурные и вспомогательные модули сервера: подключаются из точек входа (`frontend.php`, `cli.php` и т.д.) и из API/backend’ов. Здесь нет «подключаемых плагинов» уровня `server/backends/` — это **библиотека функций и мелких классов**.

## Каталог файлов

| Файл | Назначение |
|------|------------|
| [`apiExec.php`](../../../server/utils/apiExec.php) | HTTP-запросы к API через cURL (метод, URL, JSON, Bearer); для скриптов и интеграций. |
| [`apiResponse.php`](../../../server/utils/apiResponse.php) | Функция `response()` с расширенной картой HTTP-кодов и текстов (ориентир — стиль mobile API); **не путать** с `response.php`. |
| [`clickhouse.php`](../../../server/utils/clickhouse.php) | Класс `clickhouse`: HTTP-доступ к ClickHouse (сессии, запросы). |
| [`clearCache.php`](../../../server/utils/clearCache.php) | `clearCache($uid)` — удаление ключей `CACHE:FRONT:*:uid` или всех `CACHE:*` при `$uid === true`. |
| [`cleanup.php`](../../../server/utils/cleanup.php) | `cleanup()` — для каждого backend из конфига вызывает `cleanup()`. |
| [`debug.php`](../../../server/utils/debug.php) | `debugOn`, `debugMsg`, `logMsg`; при отладке может писать в `accounting->raw`. |
| [`email.php`](../../../server/utils/email.php) | `eMail($config, $to, $subj, $text)` — отправка письма через PHPMailer (SMTP из конфига). |
| [`error.php`](../../../server/utils/error.php) | `getLastError()` / `setLastError()` — глобальная последняя ошибка API. |
| [`forgot.php`](../../../server/utils/forgot.php) | Сценарий восстановления пароля (`forgot($params)`), вызывается из `frontend.php` для `/accounts/forgot`; Redis, письмо со ссылкой. |
| [`functions.php`](../../../server/utils/functions.php) | Крупный набор общих функций: `checkInt`, `checkStr`, `GUIDv4`, `array_diff_assoc_recursive`, и др. |
| [`i18n.php`](../../../server/utils/i18n.php) | `language()`, `i18n()` — язык из `Accept-Language` / конфига, подстановка строк перевода. |
| [`installCrontabs.php`](../../../server/utils/installCrontabs.php) | `installCrontabs()` — вписывает секцию заданий RBT в crontab пользователя (маркеры `## RBT crons …`). |
| [`levenshtein.php`](../../../server/utils/levenshtein.php) | `mb_levenshtein` / ratio для UTF-8 (поиск/нечёткое сравнение строк). |
| [`loader.php`](../../../server/utils/loader.php) | `loadBackend`, `loadConfiguration`, `loadExtension`, `loadDevice` — см. [отдельная страница](./loader.ru.md). |
| [`PDOExt.php`](../../../server/utils/PDOExt.php) | Расширение PDO для проекта — см. [отдельная страница](./PDOExt.ru.md). |
| [`polyfills.php`](../../../server/utils/polyfills.php) | Полифиллы (например `apache_request_headers`), если нет в окружении. |
| [`purifier.php`](../../../server/utils/purifier.php) | `htmlPurifier()` — очистка HTML через HTMLPurifier. |
| [`reindex.php`](../../../server/utils/reindex.php) | Индексация методов API в БД — см. [отдельная страница](./reindex.ru.md). |
| [`response.php`](../../../server/utils/response.php) | `response($code, $data)` — JSON-ответ, заголовок `X-Last-Error`, лог `accounting`; основной путь для Web UI. |

## Подробная документация

- [`PDOExt.php`](./PDOExt.ru.md)
- [`loader.php`](./loader.ru.md)
- [`reindex.php`](./reindex.ru.md)

См. также [обзор сервера](../overview.ru.md) и [индекс документации](../../README.ru.md).
