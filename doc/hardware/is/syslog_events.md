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
Opening door by code 77777, apartment 12
```
#### Код не существует
```
Got invalid code 44444 from MC
```

### RFID
#### Ключ есть в базе
```
Opening door by RFID 0000003375A686, apartment 0
```
#### Ключ не существует
```
RFID 00000033753EFB is not present in database
```

### API
#### Основная дверь
```
Opening main door by API command
```
#### Доп. дверь
```
Opening second door by API command
```

### DTMF
```
Open main door by DTMF
```

### Трубка
```
Opening door by CMS handset for apartment 12
Open from handset!
```

### Кнопка
```
Main door button press
```

## Детектор движения
#### Старт
```
EVENT: Detected motion in 0 areas. Min area size = 0, max area size = 249344
```
#### Стоп
```
НЕТ
```
#### Отправка снапшота не сервер при обнаружении движения
```
SendSnapshotHTTP: get response with code 200
```

## Звонки
### Начало звонка
#### Обычный режим
```
Calling to 12 flat...
```
#### Режим калитки с префиксом
```
Calling to 1 house 12 flat...
```
### Все вызовы завершены
```
All calls are done for apartment 12
```
Данного события не будет, если звонок сброшен с панели или не был отвечен
### Аналог
#### Квартира существует, трубка подключена, начало звонка
```
CMS handset call started for apartment 12
```
#### Начало разговора
```
CMS handset talk started for apartment 12
```
#### Вызов завершен
```
CMS handset call done for apartment 12, handset is down
```
#### Квартира существует, трубка не подключена
```
CMS handset is not connected for apartment 1, aborting CMS call
```
### SIP
#### Попытка вызова
```
Calling sip:12@192.168.13.60:5060 through account
```
#### Вызов в процессе
```
Baresip event CALL_PROGRESS
```
#### Начало разговора
```
Baresip event CALL_ESTABLISHED
```
#### Завершение звонка
```
Baresip event CALL_CLOSED
```
#### Вызов завершен
```
SIP call done for apartment 12, handset is down
```
#### Входящий SIP звонок
```
Baresip event CALL_INCOMING
Incoming call to sip:12@192.168.13.137:5060 (12)
```
