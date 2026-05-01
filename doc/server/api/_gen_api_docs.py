#!/usr/bin/env python3
"""Generate doc/server/api/*/README*.md and refresh doc/server/api/README*.

Re-run after adding PHP endpoints: it rescans server/api/<module>/*.php and
re-merges per-endpoint pages from the previous README (## Подробная документация
/ ## Index / ## Содержание) when present.
"""
from __future__ import annotations

from pathlib import Path

RBT_ROOT = Path(__file__).resolve().parents[3]
SERVER_API = RBT_ROOT / "server" / "api"
DOC_API = Path(__file__).resolve().parent

ROUTING_RU = """## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\\<module>\\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\\<module>\\custom\\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).
"""

ROUTING_EN = """## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\\<module>\\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\\<module>\\custom\\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).
"""


def list_php(module: str) -> list[str]:
    d = SERVER_API / module
    if not d.is_dir():
        return []
    return sorted(p.name for p in d.glob("*.php"))


def build_table(module: str, names: list[str], note_ru: str | None = None, note_en: str | None = None) -> tuple[str, str]:
    rows_ru = [
        "## Каталог endpoint-файлов",
        "",
        f"| Файл | Путь (относительно `/api/{module}`) |",
        "|------|----------------------------------------|",
    ]
    rows_en = [
        "## Endpoint files",
        "",
        f"| File | Path (under `/api/{module}`) |",
        "|------|-------------------------------------|",
    ]
    for n in names:
        ep = n[:-4]
        rows_ru.append(f"| `{n}` | `/{ep}` |")
        rows_en.append(f"| `{n}` | `/{ep}` |")
    if note_ru:
        rows_ru.extend(["", note_ru])
    if note_en:
        rows_en.extend(["", note_en])
    return "\n".join(rows_ru), "\n".join(rows_en)


def default_blurb(module: str) -> tuple[str, str, tuple[str, str] | None]:
    return (
        f"HTTP API раздела `{module}` (`server/api/{module}/`).",
        f"HTTP API for `{module}` (`server/api/{module}/`).",
        None,
    )


# Russian / English purpose + optional (note_ru, note_en) under the table
BLURB: dict[str, tuple[str, str, tuple[str, str] | None]] = {
    "accounts": (
        "Учётные записи операторов и группы: пользователи, группы, состав группы. Отдельно от маршрута `/api/…` поддержан публичный сценарий восстановления пароля — см. ниже в подробных страницах.",
        "Operator accounts and groups: users, groups, membership. Password recovery may use a non-`/api/...` path — see detailed pages below.",
        None,
    ),
    "addresses": (
        "Адресная иерархия (регион → … → дом), поиск, избранное.",
        "Address hierarchy (region → … → house), search, favorites.",
        None,
    ),
    "authentication": (
        "Вход в систему, выход, настройка двухфакторной аутентификации.",
        "Login, logout, two-factor authentication.",
        None,
    ),
    "authorization": (
        "Матрица прав на методы API, доступные методы для текущего пользователя, массовые права.",
        "API method rights matrix, allowed methods, bulk rights.",
        None,
    ),
    "billing": (
        "Интеграция с биллингом: подписки/данные абонента, адреса для импорта.",
        "Billing integration: subscriptions/subscriber data, addresses import.",
        None,
    ),
    "cameras": (
        "Реестр камер, единичная камера, снимок (camshot).",
        "Camera registry, single camera, camshot.",
        None,
    ),
    "cdr": (
        "CDR / детализация вызовов.",
        "CDR and call detail records.",
        None,
    ),
    "companies": (
        "Организации (УК, подрядчики): список и карточка.",
        "Organizations (management companies, contractors): list and CRUD.",
        None,
    ),
    "configs": (
        "Справочники моделей домофонов, камер и CMS для UI.",
        "Reference lists for domophone/camera models and CMS types.",
        None,
    ),
    "contacts": (
        "Контакты: список и единичный контакт.",
        "Contacts directory: list and single contact.",
        None,
    ),
    "cs": (
        "Электронные таблицы (csheet): листы, ячейки, резервирование.",
        "Spreadsheet-like csheets: sheets, cells, reservation.",
        None,
    ),
    "custom": (
        "Точка расширения API под кастомизации проекта (`custom.php`).",
        "Project-specific API hook (`custom.php`).",
        None,
    ),
    "files": (
        "Файловое хранилище: загрузка, выдача, метаданные.",
        "File storage: upload, download, metadata.",
        None,
    ),
    "geo": (
        "Геоподсказки адресов для форм (backend `geocoder`).",
        "Address/geo suggestions for forms (`geocoder` backend).",
        None,
    ),
    "houses": (
        "Дома, подъезды, квартиры, домофоны, CMS, камеры объекта, поиск, автоконфигурация.",
        "Houses, entrances, flats, domophones, CMS, house cameras, search, autoconfigure.",
        None,
    ),
    "inbox": (
        "Входящие сообщения абонента (мобильное приложение).",
        "Subscriber inbox messages (mobile app).",
        None,
    ),
    "mkb": (
        "Kanban-доски и карточки (MKB), отправка, чужие доски.",
        "Kanban boards/cards (MKB), send, shared desks.",
        None,
    ),
    "mqtt": (
        "Публичная конфигурация MQTT для клиентов (без секретов).",
        "MQTT settings exposed to clients (non-secret subset).",
        None,
    ),
    "notes": (
        "Пользовательские заметки в Web UI: список, поиск, порядок.",
        "Per-user notes in the web UI: list, search, reorder.",
        None,
    ),
    "providers": (
        "Реестр внешних провайдеров и JSON-конфигурация.",
        "External provider registry and JSON configuration.",
        None,
    ),
    "queues": (
        "Очередь фоновых задач (просмотр/управление из UI).",
        "Background task queue (admin UI).",
        None,
    ),
    "server": (
        "Служебные endpoint’ы: версия, `systemInfo`, сброс кеша.",
        "Operational endpoints: version, `systemInfo`, cache clear.",
        None,
    ),
    "subscribers": (
        "Абоненты, устройства, ключи, поиск, камеры квартиры.",
        "Subscribers, devices, keys, search, flat cameras.",
        None,
    ),
    "tt": (
        "Тикет-система (TT): проекты, задачи, workflow, вложения, фильтры.",
        "Ticketing (TT): projects, issues, workflow, attachments, filters.",
        (
            "*Файлы `lib.php` и `json.php` не являются отдельными маршрутами — подключаются из других endpoint’ов.*",
            "*`lib.php` and `json.php` are not standalone routes — included by other endpoints.*",
        ),
    ),
    "ud363": (
        "HTTP upload/slot по смыслу XEP-0363; имя — отсылка к номеру XEP, не к «железу». Подробности: `doc/server/entrypoints/ud363*.md`.",
        "HTTP upload/slot flow (XEP-0363 by intent); name references the XEP number, not hardware. See `doc/server/entrypoints/ud363*.md`.",
        None,
    ),
    "user": (
        "Текущий пользователь Web UI: профиль, настройки, sudo, аватар.",
        "Current web user: profile, settings, sudo, avatar.",
        None,
    ),
}


def readme_body(module: str, lang: str) -> str:
    purpose_ru, purpose_en, notes = BLURB.get(module, default_blurb(module))
    names = list_php(module)
    nr = ne = None
    if notes:
        nr, ne = notes
    tab_ru, tab_en = build_table(module, names, nr, ne)
    if lang == "ru":
        title = f"# API `{module}` (`server/api/{module}/`)"
        parts = [
            title,
            "## Назначение",
            purpose_ru,
            ROUTING_RU,
            tab_ru,
            "См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).",
        ]
        return "\n\n".join(parts)
    title = f"# `{module}` API (`server/api/{module}/`)"
    parts = [
        title,
        "## Purpose",
        purpose_en,
        ROUTING_EN,
        tab_en,
        "See also the [API index](../README.md) and [`api.php`](../api.md).",
    ]
    return "\n\n".join(parts)


def extract_legacy_ru(old: str) -> str | None:
    mk = "## Подробная документация"
    if mk in old:
        return old.split(mk, 1)[1].strip()
    if "## Содержание" in old:
        return old[old.find("## Содержание") :].strip()
    return None


def extract_legacy_en(old: str) -> str | None:
    mk = "## Detailed pages"
    if mk in old:
        tail = old.split(mk, 1)[1].strip()
        for drop in ("## Index\n", "## Contents\n"):
            if tail.startswith(drop):
                tail = tail[len(drop) :].lstrip()
                break
        return tail
    for needle in ("## Index", "## Contents"):
        if needle in old:
            return old[old.find(needle) :].strip()
    return None


def main() -> None:
    modules = sorted(p.name for p in SERVER_API.iterdir() if p.is_dir())
    old_snap: dict[tuple[str, str], str] = {}
    for m in modules:
        for suf in ("README.ru.md", "README.md"):
            p = DOC_API / m / suf
            if p.exists():
                old_snap[(m, suf)] = p.read_text(encoding="utf-8")

    for module in modules:
        doc_dir = DOC_API / module
        doc_dir.mkdir(parents=True, exist_ok=True)
        ru = readme_body(module, "ru")
        en = readme_body(module, "en")
        key_ru = (module, "README.ru.md")
        key_en = (module, "README.md")
        if key_ru in old_snap:
            leg = extract_legacy_ru(old_snap[key_ru])
            if leg:
                ru = ru + "\n\n---\n\n## Подробная документация\n\n" + leg
        if key_en in old_snap:
            leg = extract_legacy_en(old_snap[key_en])
            if leg:
                en = en + "\n\n---\n\n## Detailed pages\n\n" + leg

        (doc_dir / "README.ru.md").write_text(ru, encoding="utf-8")
        (doc_dir / "README.md").write_text(en, encoding="utf-8")
        print("wrote", module)

    lines_ru = [
        "# Server API (`server/api/`)",
        "",
        "Реализация HTTP API для Web UI и связанных клиентов: классы в `server/api/<module>/`, базовый контракт в [`api.php`](./api.ru.md). Диспетчеризация — [`server/frontend.php`](../../entrypoints/frontend.ru.md) (путь `/api/<module>/<endpoint>`).",
        "",
        "## Общие материалы",
        "",
        "- [Базовый класс `api.php`](./api.ru.md)",
        "",
        "## Каталог разделов API",
        "",
        "| Раздел | Документация |",
        "|--------|----------------|",
    ]
    lines_en = [
        "# Server API (`server/api/`)",
        "",
        "HTTP API for the web UI and related clients: handlers under `server/api/<module>/`, shared contract in [`api.php`](./api.md). Routing via [`server/frontend.php`](../../entrypoints/frontend.md) (`/api/<module>/<endpoint>`).",
        "",
        "## Shared docs",
        "",
        "- [`api.php` base class](./api.md)",
        "",
        "## API sections",
        "",
        "| Section | Documentation |",
        "|---------|----------------|",
    ]
    for m in modules:
        lines_ru.append(f"| `{m}` | [README](./{m}/README.ru.md) |")
        lines_en.append(f"| `{m}` | [README](./{m}/README.md) |")

    lines_ru.extend(
        [
            "",
            "### Примечание",
            "",
            "У части разделов есть отдельные страницы по endpoint’ам — см. блок «Подробная документация» в соответствующем `README`.",
        ]
    )
    lines_en.extend(
        [
            "",
            "### Note",
            "",
            "Some sections include per-endpoint pages — see **Detailed pages** in that section README.",
        ]
    )

    (DOC_API / "README.ru.md").write_text("\n".join(lines_ru) + "\n", encoding="utf-8")
    (DOC_API / "README.md").write_text("\n".join(lines_en) + "\n", encoding="utf-8")
    print("updated main README")


if __name__ == "__main__":
    main()
