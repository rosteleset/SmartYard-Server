## QTECH Syslog Event

используется обратный порядок при считывании ключа

```
Модель:     QDB-27C-H
Версия ПО:  227.221.3.66
```

### Открытие двери используя публичный (глобальный) код доступа панели

```
EVENT:401:55555:-PublicKey:Open Door By Code, Code:55555, Apartment No -PublicKey
```

### Открытие двери используя персональный код квартиры
Цифровой код, длина 5 символов. Можно назначить свой, на beward только генерация случайного кода
```
EVENT:400:42999:11:Open Door By Code, Code:42999, Apartment No 11
```

### Доступ по ключу, доступ разрешен

```
EVENT:101:803512EA6C2D04::Open Door By Card, RFID Key:803512EA6C2D04, Apartment No
```

### Доступ по ключу, доступ не разрешен

```
EVENT:201:B8E4D479:Open Door By Card Failed! RF Card Number:B8E4D479
```

### Вскрытие корпуса

```
EVENT:200: Attempt to dismantle
```

### Детектор движения в кадре

Старт:
некорректно работает функционал панели.
По событию можно дополнительно уведомить "Уведомить по FTP" что и отражается в syslog

```
EVENT:000:20221007144003_192.168.13.126.jpg:Send Photo:20221007144003_192.168.13.126.jpg
```

Стоп: отсутствует

### Проверка жизнеспособности устройства.

Панель регулярно отправялеет это событие, можно его отфильтровать и не логировать

```
EVENT:000:System Log Service : Heart Beat
```

### Получение / продлоение аренды ip адреса
```
EVENT:000:192.168.13.126:IP CHANGED, Current IP:192.168.13.126
```

### SIP - регистрация
```
EVENT:300:1:100001:SIP registration is OK, Account ID:1, Accout User:100001
```
```
EVENT:301:1:100001:SIP registration is failed, Account ID:1, Account User:100001
```

### Звонок завершен
```
EVENT:000:Finished Call'
```

### Набор номера с вызывной панели

```
EVENT:700:Prefix:12,Analog Number:12, Status:1
EVENT:700:Prefix:12,Replace Number:1000000001, Status:0
```

### Авторизация через WEB-GUI
```
EVENT:000:Login:Web:admin
```
### Открытие двери 
Вызов абонента в кв №1, открытие двери из приложения. (DTMF Symbol 1)
```
EVENT:106:1:1:Open Door By DTMF, DTMF Symbol 1 ,Apartment No 1
```


---
### События по нажатию кнопки открытия двери
```
EVENT:000:Time:15:27:14:Input1:Low
EVENT:102:INPUTA:Exit button pressed,INPUTA
EVENT:104:1:The Door is opened! Relay ID:1
EVENT:000:Time:15:27:14:Input1:High
EVENT:103:Exit button release
```