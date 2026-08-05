# RBT Agent HTTP protocol v2

Этот документ задаёт точный wire contract подписанных короткоживущих HTTPS-запросов между
[RBTAgent](https://gitap.ru/SesameWare/RBTAgent) и RBT.

## Транспорт

- только HTTPS с обычной проверкой CA chain и hostname;
- Agent всегда инициирует запрос;
- JSON body кодируется один раз и подписываются фактически отправляемые bytes;
- query string в signed endpoints не используется;
- максимальное допустимое расхождение часов по умолчанию составляет 60 секунд.

## Headers

Каждый запрос и ответ содержит:

```text
X-RBT-Agent-Signature-Version: 2
X-RBT-Agent-Signer-ID: <agentId-or-controllerId>
X-RBT-Agent-Key-ID: sha256:<hex>
X-RBT-Agent-Timestamp: <RFC3339 UTC without fractional seconds>
X-RBT-Agent-Request-ID: <globally unique id>
X-RBT-Agent-Sequence: <positive uint64>
X-RBT-Agent-Content-SHA256: <lowercase hex SHA-256 of exact body bytes>
X-RBT-Agent-Signature: <unpadded standard Base64 Ed25519 signature>
```

Ответ повторяет `requestId` и `sequence` исходного запроса, но подписывается application key controller.

## Canonical payload

Поля соединяются символом LF без завершающего LF:

```text
rbt-agent-http-signature-v2
<request|response>
<UPPERCASE HTTP method>
<absolute path without query>
<0 for request or HTTP response status>
<body sha256>
<signer id>
<key id>
<RFC3339 UTC timestamp>
<request id>
<positive sequence>
```

Подписывается canonical payload, а не повторно сериализованный JSON. Это исключает расхождения PHP и Go по порядку JSON keys, escaping и числовым значениям.

Protocol v2 использует только идентификаторы RBTAgent. Он несовместим с
предыдущим wire contract: Agent и controller должны обновляться согласованно.

## Replay protection

RBT принимает запрос только если одновременно выполнены условия:

- timestamp входит в разрешённое окно;
- `requestId` ещё не использовался этим Agent;
- `sequence` больше последнего принятого значения;
- body hash совпадает;
- Ed25519 signature корректна;
- key ID соответствует public key paired Agent.

Пара `(agentId, requestId)` хранится не меньше окна replay-защиты. Последний принятый sequence хранится постоянно.

## Test vector

```text
seed:       000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f
publicKey:  A6EHv/POEL4dcN0Y50vAmWfk1jCbpQ1fHdyGZBJVMbg
keyId:      sha256:56475aa75463474c0285df5dbf2bcab73da651358839e9b77481b2eab107708c
method:     POST
path:       /rbt-agent/v2/sync
timestamp:  2026-08-02T10:11:12Z
requestId:  req-01JTESTVECTOR
sequence:   42
body:       {"agentId":"agent-test","appliedGeneration":7}
signature:  tDcH8BnPX7E/Xh9x0ATOLjv7mzsvLw79qNOrjbPvI71ZvfyAgookbx4LV/oCEENS/KOhpZAuUjxxf+gDclizBQ
```

Go и PHP tests обязаны проверять этот же вектор.
