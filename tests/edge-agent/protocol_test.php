<?php

declare(strict_types=1);

use SesameWare\RbtAgent\Protocol;

require_once __DIR__ . '/../../server/edge-agent-controller/lib/Protocol.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertThrows(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$body = '{"agentId":"agent-test","appliedGeneration":7}';
$publicKey = 'A6EHv/POEL4dcN0Y50vAmWfk1jCbpQ1fHdyGZBJVMbg';
$keyId = 'sha256:56475aa75463474c0285df5dbf2bcab73da651358839e9b77481b2eab107708c';
$signature = 'tDcH8BnPX7E/Xh9x0ATOLjv7mzsvLw79qNOrjbPvI71ZvfyAgookbx4LV/oCEENS/KOhpZAuUjxxf+gDclizBQ';
$metadata = [
    'direction' => 'request',
    'method' => 'POST',
    'path' => '/rbt-agent/v2/sync',
    'statusCode' => 0,
    'bodySha256' => '476479c031f11b90e29d84cbac96166ed61cea02cd945dad5de6390002e8fc03',
    'signerId' => 'agent-test',
    'keyId' => $keyId,
    'timestamp' => '2026-08-02T10:11:12Z',
    'requestId' => 'req-01JTESTVECTOR',
    'sequence' => 42,
];

assertSameValue('2', Protocol::VERSION, 'signature version mismatch');
assertSameValue('rbt-agent-http-signature-v2', Protocol::DOMAIN, 'signature domain mismatch');
assertSameValue('X-RBT-Agent-Signature-Version', Protocol::HEADER_SIGNATURE_VERSION, 'signature version header mismatch');
assertSameValue('X-RBT-Agent-Signature', Protocol::HEADER_SIGNATURE, 'signature header mismatch');

$canonical = implode("\n", [
    'rbt-agent-http-signature-v2',
    'request',
    'POST',
    '/rbt-agent/v2/sync',
    '0',
    '476479c031f11b90e29d84cbac96166ed61cea02cd945dad5de6390002e8fc03',
    'agent-test',
    $keyId,
    '2026-08-02T10:11:12Z',
    'req-01JTESTVECTOR',
    '42',
]);

assertSameValue($canonical, Protocol::canonical($metadata), 'canonical payload mismatch');
assertSameValue($keyId, Protocol::keyId($publicKey), 'key ID mismatch');
Protocol::verify($publicKey, $metadata, $signature, $body);
Protocol::validateTimestamp($metadata, new DateTimeImmutable('2026-08-02T10:12:00Z'), 60);

$seed = implode('', array_map(chr(...), range(0, 31)));
$keyPair = sodium_crypto_sign_seed_keypair($seed);
$secretKey = Protocol::encodeBase64(sodium_crypto_sign_secretkey($keyPair));
assertSameValue($signature, Protocol::sign($secretKey, $metadata), 'cross-language signature mismatch');

assertThrows(
    static fn () => Protocol::verify($publicKey, $metadata, $signature, $body . ' '),
    'tampered body unexpectedly verified',
);
assertThrows(
    static fn () => Protocol::validateTimestamp($metadata, new DateTimeImmutable('2026-08-02T10:20:00Z'), 60),
    'stale timestamp unexpectedly accepted',
);

fwrite(STDOUT, "protocol_test: ok\n");
