# Авторизация RBT-token через SesamePortal

RBT локально вычисляет персональный токен для `{subscriberId, hostname, имя потока}`.
Portal получает только секреты streamer при ротации. Обращений к Portal при
формировании списка камер и таблицы пользовательских токенов нет.

## Настройка RBT

В `backends.dvr.servers[]` для streamer типа `sesame` или `flussonic`:

```json
{
  "type": "sesame",
  "url": "https://video.example.org:443",
  "authbackend": {
    "url": "https://portal.example.org/api/rbt/v1/secrets",
    "management_token": "<индивидуальный management_token RBT>",
    "rotation_interval": 43200,
    "secret_ttl": 86400
  }
}
```

Непустой `authbackend.url` включает режим. Отдельный флаг не используется.
Параметры `management_token` самого DVR и `authbackend.management_token`
независимы. Интервал и TTL задаются в секундах; значения по умолчанию — 43200
и 86400. Минимальный интервал — 120 секунд для ежеминутного cron, TTL строго
больше интервала. Нужны 64-битный PHP, phpredis и cURL.

Пара `{hostname, имя потока}` должна однозначно определять камеру среди всех
DVR, с которыми работают подключённые RBT. Hostname приводится к нижнему
регистру без конечной точки; IPv6 — к сжатой записи. Разные DNS-псевдонимы
автоматически не объединяются. Порт и схема HTTP/HTTPS в подпись не входят.
Для выбора настроек streamer и локальных Redis-ключей RBT продолжает
использовать hostname и порт из уже существующего `server.url`.

## Настройка Portal и DVR

Добавьте в конфигурацию Portal:

```php
'rbt_authbackend' => [
    'clients' => [
        'rbt-main' => [
            'management_token' => '<тот же management_token, что настроен в RBT>',
        ],
    ],
],
```

Используйте независимые случайные токены минимум из 32 случайных байт.
`clients.*.management_token` содержит обычный токен: скопируйте одно и то же
значение в конфигурации RBT и Portal, без вычисления SHA. Portal сравнивает
его через `hash_equals`. Один management_token идентифицирует один RBT.
Доступ к файлу конфигурации должен быть ограничен администратором и PHP.
Пример генерации случайного токена:

```php
$token = bin2hex(random_bytes(32));
```

У всех DVR общий callback `/api/sesamedvr/auth`. В настройке SesameDVR
`authBackendUrl` укажите:

```text
https://portal.example.org/api/sesamedvr/auth
```

SesameDVR передаёт существующие параметры `host`, `name`, `token` и остальные
параметры проверки. Portal находит секрет по глобально уникальному `secret_id`
и проверяет HMAC для пары `{host, name}`. Списка DVR в конфигурации Portal,
дополнительного токена DVR и индивидуальных callback-адресов нет.

Hostname в callback должен совпадать с hostname URL камеры в RBT. В текущем
SesameDVR `host` берётся из HTTP Host запроса к потоку. Поэтому DVR/reverse
proxy должен принимать только свои разрешённые Host, отклонять посторонние
и не подменять публичный Host внутренним адресом upstream. Иначе клиент,
способный подставить Host другого DVR, сможет предъявить его токен для
одноимённого потока. Portal сам не может проверить, какой Host разрешён
конкретному DVR, без дополнительной конфигурации или контекста от DVR.

RBT доверен как администратор: регистрация не проверяет существование камеры
или абонента в Portal. Действующий токен разрешает live, архив и превью только
указанного потока на указанном DVR. PTZ этим токеном не предоставляется.
Обычные пользовательские токены Portal продолжают проверяться прежним способом.

### IP whitelist в nginx

Новый installer добавляет отдельный `location = /api/rbt/v1/secrets`, который
передаёт запрос непосредственно в PHP-FPM, и файл
`/etc/nginx/snippets/sesame-portal-rbt-allow.conf`. По умолчанию доступ закрыт.
Добавьте исходящие IP/CIDR RBT перед `deny all`:

```nginx
allow 192.0.2.10;
deny all;
```

Используйте HTTPS. При наличии доверенного reverse proxy настройте nginx
real IP только для этого proxy. Portal не доверяет клиентским заголовкам для
проверки whitelist и сам whitelist не реализует.

Обновление только PHP-кода через updater не меняет существующий nginx site.
Для такой установки добавьте в HTTPS server block (пути адаптируйте):

```nginx
location = /api/rbt/v1/secrets {
    allow 192.0.2.10;
    deny all;
    client_max_body_size 4k;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /opt/sesame-portal/current/public/index.php;
    fastcgi_param HTTPS on;
    fastcgi_param SESAME_PORTAL_STATE_DIR /var/lib/sesame-portal;
    fastcgi_param SESAME_PORTAL_CONFIG /var/lib/sesame-portal/config.php;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}
```

Не используйте для этого location внутренний redirect через `try_files`:
он может перевести обработку в другой location без IP-ограничений. Уберите
query string из access log callback `/api/sesamedvr/auth` либо отключите его
access log: URL содержит пользовательский токен.

## Регистрация секрета

`POST /api/rbt/v1/secrets`, `Authorization: Bearer <management_token>`,
`Content-Type: application/json` (до 4096 байт):

```json
{
  "secret_id": 12345678,
  "secret": "<стандартный Base64 от 32 случайных байт>",
  "valid_from": 1791583200,
  "ttl": 86400
}
```

Времена в примере иллюстративные. `valid_from` — Unix timestamp в секундах,
может быть в будущем. `secret_id` — целое от 1 до 4294967295, уникальное
среди всех хранимых секретов Portal независимо от DVR и RBT.
`expires_at = valid_from + ttl`, время регистрации не влияет на этот срок.

Ответ `200`:

```json
{
  "registered": true,
  "secret_id": 12345678,
  "valid_from": 1791583200,
  "expires_at": 1791669600
}
```

Повтор с теми же параметрами и от того же RBT возвращает тот же результат.
Другой секрет, владелец или сроки для занятого `secret_id` дают
`409`; RBT сохранит новый ID и повторит регистрацию на следующем cron. Остальные ответы: `401` —
авторизация, `405` — метод, `413` — размер, `415` — формат, `400/422` —
некорректные данные, `503` — временная ошибка хранения. Истёкшие секреты не
регистрируются. Секрет и management_token не возвращаются и не логируются.

## Redis и ротация

Существующий `cli.php --cron=minutely` вызывает DVR backend. Он заранее
регистрирует следующий секрет за `min(600, rotation_interval / 2)` секунд
до переключения. Если зарегистрированного секрета ещё нет, создаёт его с
`valid_from = now`. Секрет сохраняется до HTTP-запроса:

```text
rbt:dvr:<hostname:port>:authbackend:current
rbt:dvr:<hostname:port>:authbackend:next
```

Каждый ключ содержит полную JSON-запись:

```text
secret_id, secret (Base64), valid_from, issue_until, expires_at,
registered=false|true, context
```

`current` содержит подтверждённый секрет для выдачи токенов. `next` —
следующий секрет, ожидающий регистрации или уже подтверждённый Portal.
После переключения `next` может временно содержать копию `current` до
подготовки следующего секрета; cron распознаёт эту копию по `secret_id`.

`registered=false` соответствует pending. В `context` хранится хеш адреса
Portal, management_token и версии формулы подписи: изменение адреса,
учётных данных или формулы требует новой регистрации. Сами учётные данные
в эти Redis-записи не копируются.
Redis должен быть общим для процессов одного RBT; отдельные установки RBT
должны использовать отдельный Redis/DB для этого пространства ключей.

JSON и TTL записываются одной командой `SETEX`. При каждой записи TTL
вычисляется как `expires_at - now`; исходный срок не запускается заново.
`issue_until = valid_from + rotation_interval` и `expires_at` фиксируются
при создании; изменение настроек применяется к новым секретам.
Cron повторяет регистрацию того же pending-секрета, затем сохраняет
`registered=true` в `next`. В момент переключения копирует запись в
`current`. Предыдущий секрет больше не нужен RBT; Portal хранит его до
`expires_at`. Redis автоматически удаляет оставшиеся записи по TTL.

На границе интервала getter может прочитать уже подтверждённый `next` до
очередного cron: сетевого вызова нет. Внутри одного запроса настройки
проверяются и кешируются один раз на streamer; подтверждённый секрет
кешируется до `issue_until`. Обычно нужен один Redis GET на streamer за
запрос, на границе ротации — два. Чтение не меняет TTL.

При обновлении с прежней структуры Redis сначала запустите minutely cron
новой версии для регистрации и заполнения `current`/`next`. Старые
`secret:<id>`, `current_secret_id` и `next_secret_id` больше не читаются
и удалятся по своим TTL. Формат playback-токена и API Portal не меняются.

Избыточные секреты при параллельном cron допустимы; Portal хранит все
неистёкшие. Если нет подтверждённого секрета в интервале выдачи, RBT возвращает
ошибку `rbt_authbackend_secret_unavailable`; перехода на статический токен нет.
Выданные ранее токены продолжают проверяться до `expires_at`.

## Формат R1

Токен — 41 ASCII-символ:

```text
R1. + Base64URL_without_padding(header || mac)
header = uint32_be(secret_id) || uint64_be(subscriberId)     # 12 байт
message = ASCII("R1.") || header
          || uint16_be(byte_length(hostname)) || ASCII(hostname)
          || uint16_be(byte_length(name)) || UTF8(name)
mac = first_16_bytes(HMAC-SHA256(secret, message))           # 16 байт
```

Для служебного `subscriberId=-1` UID равен `0xffffffffffffffff`.
Положительные subscriberId берутся из существующей авторизации RBT.
RBT берёт hostname из URL `dvrStream`, а имя потока — из первого компонента
пути, декодируя percent-encoding ровно один раз. Portal нормализует hostname
из callback-параметра `host` (возможный порт отбрасывается) и берёт уже
декодированное PHP значение `name`, без
повторного декодирования, basename, изменения регистра или удаления слешей.
Имя: до 128 байт, первая буква/цифра, затем буквы/цифры/`.`/`_`/`-`;
поддерживаются Unicode-буквы и цифры старых потоков. Порт, завершающий
слеш, путь HLS/архива и query string в HMAC не входят. Hostname входит в HMAC,
поэтому токен не подходит для другого значения hostname в callback.

Portal требует точный формат, канонический Base64URL, действующий секрет и
сравнивает MAC через `hash_equals`. UID не является пользователем Portal.
Это bearer-токен: передача действующего токена другому человеку всё ещё даёт
доступ к этому потоку до истечения срока; HMAC ограничивает подделку и область
доступа, но не запрещает копирование токена.

## Хранение и включение

Portal хранит секреты в `rbt_authbackend_secrets` (SQLite/PostgreSQL/MySQL),
в поле `secret` в формате Base64 без шифрования. При проверке HMAC секрет
декодируется из Base64. Проверка времени выполняется
при каждом обращении к authbackend независимо от очистки. Кеш разрешений и
уже открытые соединения самого DVR могут жить по его отдельным правилам.
Существующий ежедневный `rotate-tokens` удаляет истёкшие строки. Таблица
включена в backup/restore.

На стороне Portal регистрация секретов, проверка RBT-токенов и очистка
сосредоточены в `app/RbtAuthBackend.php`. Модуль подключён к существующим
маршрутам и cron; ротацией секретов занимается RBT.

Порядок включения: настройте credentials Portal и nginx, затем конфигурацию
RBT и запустите существующий minutely cron для первой регистрации. После
этого включайте authBackend на DVR. Восстановление Redis потребует новой
регистрации через cron. Удаление клиента RBT или установка `disabled=true`
в его конфигурации Portal прекращает новые успешные проверки его RBT-токенов.
