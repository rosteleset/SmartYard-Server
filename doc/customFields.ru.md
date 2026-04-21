# customFields

## Что это такое

`customFields` в RBT позволяют расширять штатный набор полей для разных сущностей без отдельной
доработки схемы под каждый новый атрибут.

На практике это даёт возможность:

- добавлять новые поля для сущности через конфигурацию;
- хранить их значения отдельно от основной таблицы сущности;
- управлять тем, как поле выглядит в UI;
- раскладывать поля по вкладкам;
- задавать обязательность, порядок, тип редактора и варианты выбора;
- привязывать к полю дополнительную кнопку или действие через `magic_*`;
- использовать i18n-ключи в `fieldDisplay`, `fieldDescription` и `tab`.

Механизм сам по себе backend-универсальный, но в текущем `client` полноценная отрисовка и
редактирование сейчас реализованы для `apply_to = flat` в модуле `addresses/houses`.

## Где что хранится

### 1. Описание полей: `custom_fields`

Таблица `custom_fields` хранит определение поля и почти все настройки его представления.

Основные колонки:

- `apply_to` — к какой сущности относится поле, например `flat`;
- `catalog` — условная группа или источник поля, например `billing`;
- `type` — логический тип поля, например `text`, `select`, `button`;
- `field` — машинное имя поля;
- `field_display` — заголовок поля или i18n-ключ;
- `field_description` — описание поля или i18n-ключ;
- `editor` — тип редактора в форме, например `text`, `area`, `json`, `yesno`, `noyes`;
- `format` — дополнительные флаги формата, например `multiple`, `editable`;
- `required` — обязательность;
- `regex` — серверно-заданная маска для клиентской валидации;
- `add` — показывать ли поле в форме создания;
- `modify` — показывать ли поле в форме редактирования;
- `tab` — вкладка, на которой поле должно отображаться;
- `weight` — порядок поля;
- `magic_class`, `magic_function`, `magic_hint` — параметры дополнительной кнопки/действия.

Отдельной таблицы только для "настроек представления" здесь нет: большинство таких настроек
хранятся прямо в `custom_fields`.

### 2. Варианты выбора: `custom_fields_options`

Если поле имеет тип выбора, его варианты хранятся в `custom_fields_options`.

Основные колонки:

- `custom_field_id` — ссылка на запись из `custom_fields`;
- `option` — значение;
- `option_display` — отображаемый текст;
- `display_order` — порядок варианта.

### 3. Значения по объектам: `custom_fields_values`

Фактические значения хранятся в `custom_fields_values`.

Основные колонки:

- `apply_to` — сущность;
- `id` — id конкретного объекта этой сущности;
- `field` — машинное имя поля;
- `value` — значение.

Важно: значения привязаны не к `custom_field_id`, а к комбинации `apply_to + id + field`.

## Как это работает в backend

Основной backend лежит в `server/backends/customFields/internal/internal.php`.

Он делает три базовые вещи:

- читает значения поля через `getValues($applyTo, $id)`;
- сохраняет значения через `modifyValues($applyTo, $id, $set, $mode = "replace")`;
- читает конфигурацию полей через `getFields($applyTo)`.

`getFields()`:

- читает строки из `custom_fields`;
- сортирует их по `weight`;
- добавляет `options` из `custom_fields_options`;
- возвращает готовую конфигурацию для UI.

`modifyValues()`:

- обновляет существующие значения;
- добавляет новые;
- в режиме `replace` удаляет поля, которых нет в `$set`;
- в режиме `patch` меняет только упомянутые поля и не трогает остальные;
- если для упомянутого поля передано пустое значение, удаляет именно это поле;
- возвращает ошибку, если insert/update/delete в БД не прошли.

Периодическая `cleanup()`:

- удаляет значения без актуального `apply_to`;
- удаляет значения для полей, которых больше нет в `custom_fields`;
- удаляет сиротские `custom_fields_options`.

## Как это используется в API для квартир

Для квартир (`flat`) используются два API-метода:

### Конфигурация полей

`GET /frontend/houses/customFieldsConfiguration`

Возвращает конфигурацию доступных custom fields, сейчас в формате:

```json
{
  "customFieldsConfiguration": {
    "flat": [ ... ]
  }
}
```

### Значения полей

`GET /frontend/houses/customFields/flat?id=<flatId>`

Возвращает значения custom fields конкретной квартиры.

`PUT /frontend/houses/customFields/flat`

Принимает:

```json
{
  "id": 123,
  "customFields": {
    "agreement": "1235",
    "addressText": "demo flat 2 block"
  }
}
```

И сохраняет значения в `custom_fields_values`.

## Что сейчас умеет UI для квартир

В `client/modules/addresses/houses.js` custom fields для `flat` уже:

- загружаются из `houses/customFieldsConfiguration`;
- читают значения из `houses/customFields/flat`;
- сохраняются как при редактировании квартиры, так и при создании новой;
- могут отображаться на нескольких вкладках;
- используют `tab` из БД;
- локализуют `fieldDisplay`, `fieldDescription` и `tab`, если там лежит i18n-ключ.

Поддерживаемые сценарии рендера:

- обычное текстовое поле;
- многострочное поле через `editor = area`;
- `email`, `tel`;
- JSON-поле через `editor = json`;
- да/нет через `editor = yesno` или `editor = noyes`;
- `select` и множественный `select`;
- редактируемый `select`, если в `format` есть `editable`;
- отдельная action-кнопка через `type = button`;
- кнопка справа от обычного поля через `magic_function + magic_class`.

Порядок и вкладки:

- порядок полей внутри одной вкладки определяется `weight`;
- вкладка берётся из `custom_fields.tab`;
- если `tab` пустой, используется общий fallback `customFields`.

### Как добавить customField на уже существующую вкладку RBT

Форма в RBT объединяет поля во вкладки по точному значению `field.tab`.

Для custom fields это работает так:

- берётся `custom_fields.tab`;
- если там i18n-ключ, клиент сначала локализует его;
- дальше поле попадает во вкладку с таким же итоговым названием.

Это означает, что для добавления customField в уже существующую вкладку нужно не придумывать
новое имя вкладки, а записать в `custom_fields.tab` тот же ключ или тот же текст, который уже
используют штатные поля этой формы.

Рекомендуемый вариант: использовать именно i18n-ключ штатной вкладки, а не локализованный текст.
Тогда одна и та же конфигурация будет правильно работать в разных языках интерфейса.

Примеры для формы квартиры (`addresses/houses`):

- `tab = 'addresses.primary'` — поле попадёт во вкладку `Основные`;
- `tab = 'addresses.cars'` — поле попадёт во вкладку `Машины`;
- `tab = 'other'` — поле попадёт во вкладку `Прочее`, если форма её показывает.

Важно:

- если записать в `tab` новое значение, которое не совпадает ни с одной штатной вкладкой, в UI
  появится новая отдельная вкладка;
- если оставить `tab` пустым, поле не попадёт в штатную вкладку `Прочее`, а уйдёт в fallback
  `customFields`;
- поэтому для попадания именно в `Прочее` нужно задавать `tab = 'other'`, а не `null` или пустую
  строку.

## Что означают `magic_class`, `magic_function`, `magic_hint`

Это UI-хуки для нестандартного поведения поля.

- `magic_function` — имя JS-функции, которую надо вызвать;
- `magic_class` — CSS-класс кнопки или иконки;
- `magic_hint` — текст подсказки или подпись для кнопки.

Есть два основных сценария:

### 1. Поле-кнопка

Если `type = button`, поле превращается в отдельную кнопку.

Такое поле:

- не хранит пользовательское значение;
- рендерится только если указан `magic_function`;
- вызывает `modules.custom[magic_function]`.

### 2. Обычное поле с кнопкой справа

Если поле не `button`, но у него заданы `magic_function` и `magic_class`, рядом с input
появляется кнопка, которая вызывает указанную функцию.

## Как пользоваться customFields

Базовый порядок такой:

### 1. Создать определение поля

Добавить запись в `custom_fields`:

- выбрать `apply_to`, например `flat`;
- задать `field`;
- задать тип поля (`type`, `editor`, `format`);
- задать пользовательский текст или i18n-ключи в `field_display`, `field_description`, `tab`;
- выставить `add`, `modify`, `required`, `weight`.

Пример для простого текстового поля:

```sql
insert into custom_fields (
    apply_to,
    catalog,
    type,
    field,
    field_display,
    field_description,
    editor,
    add,
    modify,
    tab,
    weight
) values (
    'flat',
    'custom',
    'text',
    'passportNumber',
    'Номер паспорта',
    'Дополнительный атрибут квартиры',
    'text',
    1,
    1,
    'Документы',
    100
);
```

### 2. Если нужно, добавить варианты выбора

Для `select`-полей заполнить `custom_fields_options`.

### 3. Открыть форму квартиры в UI

Если поле настроено для `flat` и разрешено по `add` или `modify`, оно появится:

- в форме создания квартиры;
- в форме редактирования квартиры.

### 4. Сохранить значение

UI отправит значения в `PUT /frontend/houses/customFields/flat`, а backend запишет их в
`custom_fields_values`.

## Billing как пример использования

Теперь `billing/subscriptions` умеет использовать `customFields` для квартир.

Сейчас через этот механизм автоматически поддерживаются поля:

- `agreement`;
- `addressText`.

При sync billing:

- backend может создать отсутствующие определения полей в `custom_fields`;
- нормализует их настройки, если это billing-поля;
- записывает значения в `custom_fields_values`.

Для этих полей используются i18n-ключи:

- `addresses.customFields.billing.tab`
- `addresses.customFields.billing.agreement.fieldDisplay`
- `addresses.customFields.billing.agreement.fieldDescription`
- `addresses.customFields.billing.addressText.fieldDisplay`
- `addresses.customFields.billing.addressText.fieldDescription`

## Как работают переводы для billing customFields

Клиент умеет локализовать `fieldDisplay`, `fieldDescription` и `tab`, если там лежат ключи.

Для billing-полей реальные переводы сейчас вынесены в client custom i18n:

- `client/modules/addresses/custom/i18n/ru.json`
- `client/modules/addresses/custom/i18n/en.json`

Там ключи лежат в пространстве имён модуля `addresses`, например:

- `customFields.billing.tab`
- `customFields.billing.agreement.fieldDisplay`

Чтобы браузер подхватил эти переводы, во frontend runtime config должен быть включён:

```json
"customSubModules": {
  "addresses": []
}
```

Речь именно о клиентском конфиге, который браузер загружает как `/config/config.json`.

## Ограничения и практические замечания

- backend-механизм generic, но клиентская поддержка сейчас сделана именно для `flat`;
- если удалить определение поля из `custom_fields`, `cleanup()` потом удалит и сиротские значения;
- если в `fieldDisplay` или `tab` лежит i18n-ключ, а нужный перевод не загружен, UI покажет сам ключ;
- если используется `type = button`, это action-элемент, а не поле хранения значения;
- `weight` влияет не только на порядок полей, но и фактически на порядок появления custom-вкладок
  в форме, потому что вкладки собираются по первому встретившемуся полю.
