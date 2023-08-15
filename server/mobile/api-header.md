Для взаимодействия между платформой и мобильными приложениями применяются веб-сервисы, работающие по протоколу REST.
Вызовы веб-сервисов осуществляются через метод POST.
Входящие параметры и ответы сервисов являются объектами в формате JSON.
Формат ответа сервиса

Name|Type|Descr
----|----|-----
code|Number|код результата (a'la HTTP)
name|String|краткое сообщение (a'la HTTP)
message|String|расшифровка (для пользователя)
data|Object|payload

Коды 2хх считаются "успешными", все остальные - ошибки, 3xx (redirect) не используются 

```
{
    "code": 200,
    "name": "OK",
    "message": "хорошо",
    "data": {
        "doorCode": "40374",
        "allowed": "t"
    }
}
```
```
{
    "code": 404,
    "name": "Not Found",
    "message": "не найдено"
}
```
В описаниях методов возвращаемые значения указаны без "обертки" в data

При голосовом вызове на устройство отправляется PUSH сообщение содержащее следующие данные (пример)
[stun* и turn* - опциональные параметры, могут отсутствовать]
```
{
    "server": "yourserver.yourdomain",
    "port": "54675",
    "transport": "tcp",
    "extension": "2000002224",
    "pass": "310b2883c53024644bcd8355fe846b67",
    "dtmf": "1",
    "stun": "stun:stun.l.google.com:19302",
    "stunTransport": "udp",
    "turn": "turn:37.235.209.140:3478",
    "turnTransport": "udp",
    "turnUsername": "test",
    "turnPassword": "123123",
    "image": "https://yourserver.yourdomain/shot/e4bb3f86073a270ec8d9291c10d26dfe.jpg",
    "live": "https://yourserver.yourdomain/live/e4bb3f86073a270ec8d9291c10d26dfe/image.jpg",
    "timestamp": "1231231",
    "ttl": "30",
    "callerId": "Домофон"
    "platform": "ios",
    "flatId": "12345",
    "flatNumber": "11",
    "baseUrl": "https://yourserver.yourdomain:543",
}
```

При отправке текстового сообщения текст и заголовок сообщения отправляются как обычно, также отправляются
следующие данные
```
{
    "messageId": "e4bb3f86073a270ec8d9291c10d26dfe",
    "action": "inbox",
    "badge": "0",
    "ext": "id расширения", // опционально
}
```
messageId - идентификатор сообщения (используется в методах delivered и readed),

badge - количество непрочитанных сообщений,

action - указывает на то как отображать (использовать) данное сообщение
- inbox - сообщение
- chat - сообщение в чате
- newAddress - доступен новый адрес
- paySuccess - платеж прошел успешно
- payError - платеж завершился с ошибкой
- videoReady - ролик готов к загрузке
- ext - сообщение для расширения
