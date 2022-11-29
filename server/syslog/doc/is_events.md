# IS Syslog Event
```
Модель: ISCOM X1
Версия ПО: 2.5.4.2 2022-11-17
```

## Открытие двери
### Публичный (глобальный) код
```
---
```

### Персональный код
#### Код есть в базе
```
UART[917]: Opening door by code 77777, apartment 12
```
#### Код не существует
```
UART[917]: Got invalid code 44444 from MC
```

### RFID
#### Ключ есть в базе
```
UART[917]: Opening door by RFID 0000003375A686, apartment 0
```
#### Ключ не существует
```
UART[917]: RFID 00000033753EFB is not present in database
```

### API
#### Основная дверь
```
API[965]: Opening main door by API command
```
#### Доп. дверь
```
API[943]: Opening second door by API command
```

### DTMF
```
LIBRE[21560]: Open main door by DTMF
```

### Трубка
```
UART[895]: Opening door by CMS handset for apartment 12
```

### Кнопка
```
UART[917]: Main door button press
```

## Детектор движения
#### Старт
```
MPP-STREAMER[890]: EVENT: Detected motion in 0 areas. Min area size = 0, max area size = 249344
```
#### Стоп
```
НЕТ
```
#### Отправка снапшота не сервер при обнаружении движения
```
MPP-STREAMER[890]: SendSnapshotHTTP: get response with code 200
```

## Звонки
### Аналог
#### Квартира существует, трубка подключена, начало звонка
```
UART[895]: CMS handset call started for apartment 12
```
#### Начало разговора
```
UART[895]: CMS handset talk started for apartment 12
```
#### Вызов завершен
```
UART[895]: CMS handset call done for apartment 12, handset is down
```
#### Квартира существует, трубка не подключена
```
UART[895]: CMS handset is not connected for apartment 1, aborting CMS call
```

## To be continued...
