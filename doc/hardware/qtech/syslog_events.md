## QTECH Syslog events

Note: the key bytes are read in reverse order.

```
Model:    QDB-27C-H
Firmware: 227.221.3.66
```

### Door opening using the public (global) panel code

```
EVENT:401:55555:-PublicKey:Open Door By Code, Code:55555, Apartment No -PublicKey
```

### Door opening using an apartment personal code

Numeric code, 5 digits. A custom code can be set; on Beward panels only a random code is generated.

```
EVENT:400:42999:11:Open Door By Code, Code:42999, Apartment No 11
```

### Access by key: allowed

```
EVENT:101:803512EA6C2D04::Open Door By Card, RFID Key:803512EA6C2D04, Apartment No
```

### Access by key: denied

```
EVENT:201:B8E4D479:Open Door By Card Failed! RF Card Number:B8E4D479
```

### Tamper / case opening

```
EVENT:200: Attempt to dismantle
```

### Motion detection in the frame

Start:

The panel feature works unreliably. You can additionally enable an “FTP notify” action which is also reflected in syslog.

```
EVENT:000:20221007144003_192.168.13.126.jpg:Send Photo:20221007144003_192.168.13.126.jpg
```

Stop: not available.

### Device heartbeat

The panel sends this event periodically; it can be filtered out and not logged.

```
EVENT:000:System Log Service : Heart Beat
```

### DHCP lease: IP obtained / renewed

```
EVENT:000:192.168.13.126:IP CHANGED, Current IP:192.168.13.126
```

### SIP registration

```
EVENT:300:1:100001:SIP registration is OK, Account ID:1, Accout User:100001
```

```
EVENT:301:1:100001:SIP registration is failed, Account ID:1, Account User:100001
```

### Call finished

```
EVENT:000:Finished Call'
```

### Dialing from the panel keypad

```
EVENT:700:Prefix:12,Analog Number:12, Status:1
EVENT:700:Prefix:12,Replace Number:1000000001, Status:0
```

### Web GUI login

```
EVENT:000:Login:Web:admin
```

### Door opening (DTMF from the app)

Call to apartment #1, then door opening from the mobile app (DTMF symbol 1).

```
EVENT:106:1:1:Open Door By DTMF, DTMF Symbol 1 ,Apartment No 1
```

---

### Exit button events

```
EVENT:000:Time:15:27:14:Input1:Low
EVENT:102:INPUTA:Exit button pressed,INPUTA
EVENT:104:1:The Door is opened! Relay ID:1
EVENT:000:Time:15:27:14:Input1:High
EVENT:103:Exit button release
```