# RBT Agent HTTP protocol v1

Этот документ задаёт точный wire contract подписанных короткоживущих HTTPS-запросов между SesameAgent и RBT.

## Транспорт

- только HTTPS с обычной проверкой CA chain и hostname;
- Agent всегда инициирует запрос;
- JSON body кодируется один раз и подписываются фактически отправляемые bytes;
- query string в signed endpoints не используется;
- максимальное допустимое расхождение часов по умолчанию составляет 60 секунд.

## Headers

Каждый запрос и ответ содержит:

```text
X-Sesame-Signature-Version: 1
X-Sesame-Signer-ID: <agentId-or-controllerId>
X-Sesame-Key-ID: sha256:<hex>
X-Sesame-Timestamp: <RFC3339 UTC without fractional seconds>
X-Sesame-Request-ID: <globally unique id>
X-Sesame-Sequence: <positive uint64>
X-Sesame-Content-SHA256: <lowercase hex SHA-256 of exact body bytes>
X-Sesame-Signature: <unpadded standard Base64 Ed25519 signature>
```

Ответ повторяет `requestId` и `sequence` исходного запроса, но подписывается application key controller.

## Canonical payload

Поля соединяются символом LF без завершающего LF:

```text
sesame-agent-http-signature-v1
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
body:       {"agentId":"agent-test","appliedGeneration":7}
signature:  cizlJa77hQqniFwTJgZgUzBEUUj8vHCIYQWq249YZdBiSax/evT0sluurZg8PXbJLMf+NQpKuUMY4pSNbmdRCg
```

Go и PHP tests обязаны проверять этот же вектор.
