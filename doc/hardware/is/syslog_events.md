# IS Syslog events

```
Model: ISCOM X1
Firmware: 2.5.4.2 (2022-11-17)
```

## Door opening

### Public (global) code

```
---
```

### Personal apartment code

#### Code exists in DB

```
Opening door by code 77777, apartment 12
```

#### Code is missing

```
Got invalid code 44444 from MC
```

### RFID

#### Key exists in DB

```
Opening door by RFID 0000003375A686, apartment 0
```

#### Key is missing

```
RFID 00000033753EFB is not present in database
```

### API

#### Main door

```
Opening main door by API command
```

#### Secondary door

```
Opening second door by API command
```

### DTMF

```
Open main door by DTMF
```

### Handset

```
Opening door by CMS handset for apartment 12
Open from handset!
```

### Exit button

```
Main door button press
```

## Motion detection

#### Start

```
EVENT: Detected motion in 0 areas. Min area size = 0, max area size = 249344
```

#### Stop

```
NO
```

#### Snapshot upload on motion detection

```
SendSnapshotHTTP: get response with code 200
```

## Calls

### Call start

#### Regular mode

```
Calling to 12 flat...
```

#### Gate mode with prefix

```
Calling to 1 house 12 flat...
```

### All calls completed

```
All calls are done for apartment 12
```

This event will not appear if the call was cancelled from the panel or not answered.

### Analog handset

#### Apartment exists, handset connected: call start

```
CMS handset call started for apartment 12
```

#### Talk started

```
CMS handset talk started for apartment 12
```

#### Call done

```
CMS handset call done for apartment 12, handset is down
```

#### Apartment exists, handset not connected

```
CMS handset is not connected for apartment 1, aborting CMS call
```

### SIP

#### Dial attempt

```
Calling sip:12@192.168.13.60:5060 through account
```

#### In progress

```
Baresip event CALL_PROGRESS
```

#### Established

```
Baresip event CALL_ESTABLISHED
```

#### Closed

```
Baresip event CALL_CLOSED
```

#### Call done

```
SIP call done for apartment 12, handset is down
```

#### Incoming SIP call

```
Baresip event CALL_INCOMING
Incoming call to sip:12@192.168.13.137:5060 (12)
```
