For interaction between the platform and mobile applications, web services operating on the REST protocol are used.
Web services calls are carried out through the POST method.
The incoming parameters and services of services are objects in JSON format.
Service response format

Name|Type|Descr
----|----|-----
code | Number | The result code (A'LA http)
name | String | Short post (a'la http)
message | String | Decryption (for the user)
data | Object | Payload

2xx codes are considered “successful”, all the rest are errors, 3xx (redirect) are not used

```
{
    "code": 200,
    "name": "OK",
    "message": "ok",
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
    "message": "Not Found"
}
```

In the descriptions of the methods, the return values ​​are indicated without "wrapper" in Data

With a voice call to the device, PUSH posts containing the following data are sent (example)
[stun* and turn* - optional parameters, may be absent]

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
    "callerId": "Intercom"
    "platform": "ios",
    "flatId": "12345",
    "flatNumber": "11",
    "baseUrl": "https://yourserver.yourdomain:543",
}
```

When sending a text message, the text and the heading of the messages are sent as usual, also sent
The following data

```
{
    "messageId": "e4bb3f86073a270ec8d9291c10d26dfe",
    "action": "inbox",
    "badge": "0",
    "Ext": "ID extensions", // optionally
}
```

Messageid - message identifier (used in Delvedred and Readed methods),

badge - the number of unread messages,

action - indicates how to display (use) this message
- inbox - message
- chat - Message in the chat
- newaddress - A new address is available
- paysuccess - payment was successful
- payerror - payment ended with an error
- videoredy - the video is ready to download
- ext - message for expansion
