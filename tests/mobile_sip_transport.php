<?php
// Run: php tests/mobile_sip_transport.php (no DB, Redis, or external services).
function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function i18n($value) { return $value; }
function loadBackend($name) { return $GLOBALS['testBackends'][$name]; }

// Exercise the real push handler without the entrypoint's DB/Redis bootstrap.
$source = file_get_contents(__DIR__ . '/../server/asterisk.php');
check(preg_match('/case "push":(.*?)case "concierge":/s', $source, $matches) === 1, 'Push handler not found');
$handler = 'switch (true) { default: ' . $matches[1] . ' }';
$sip = new class {
    public $serverConfig;
    public $stunServer;
    public $extension;
    public $stunExtension;
    function server($by, $extension) {
        check($by === 'extension', 'Server selection changed');
        $this->extension = $extension;
        return $this->serverConfig;
    }
    function stun($extension) {
        $this->stunExtension = $extension;
        return $this->stunServer;
    }
};
$isdn = new class {
    public $payloads = [];
    function push($params) { $this->payloads[] = $params; }
};
$testBackends = [
    'sip' => $sip,
    'isdn' => $isdn,
    'households' => new class {
        function getDomophone($id) { return false; }
    },
];
$params = [
    'token' => 'test-token', 'tokenType' => 0, 'hash' => 'test-hash',
    'extension' => '2000000001', 'dtmf' => '1', 'callerId' => 'Test',
    'flatId' => 1, 'domophoneId' => 1, 'flatNumber' => '1', 'bundle' => 'org.example.app',
];
$cases = [
    'legacy defaults' => [[], 'tcp', 5060],
    'legacy custom port' => [['sip_tcp_port' => 5090], 'tcp', 5090],
    'legacy empty port' => [['sip_tcp_port' => 0], 'tcp', 5060],
    'explicit TCP' => [['sip_mobile_transport' => 'tcp', 'sip_tcp_port' => 5090, 'sip_tls_port' => 8443], 'tcp', 5090],
    'TLS port alone' => [['sip_tls_port' => 8443], 'tcp', 5060],
    'null transport' => [['sip_mobile_transport' => null], 'tcp', 5060],
    'unknown transport' => [['sip_mobile_transport' => 'udp', 'sip_tcp_port' => 5090], 'tcp', 5090],
    'TLS default port' => [['sip_mobile_transport' => 'tls', 'sip_tcp_port' => 5090], 'tls', 5061],
    'TLS null port' => [['sip_mobile_transport' => 'tls', 'sip_tls_port' => null], 'tls', 5061],
    'TLS custom port' => [['sip_mobile_transport' => 'tls', 'sip_tls_port' => 8443, 'sip_tcp_port' => 5090], 'tls', 8443],
];
$runs = 0;
foreach ($cases as $name => [$settings, $transport, $port]) {
    foreach ([0 => 'android', 1 => 'ios'] as $platformId => $platform) {
        foreach ([false, 'stun:stun.example.org:3478'] as $stun) {
            $params['platform'] = $platformId;
            $sip->serverConfig = $settings + ['ip' => 'sip.example.org'];
            $sip->stunServer = $stun;
            $isdn->payloads = [];
            $before = time();
            eval($handler);
            check(count($isdn->payloads) === 1, "$name: expected one push");
            $payload = $isdn->payloads[0];
            check($payload['server'] === 'sip.example.org', "$name: server changed");
            check($payload['transport'] === $transport, "$name: wrong transport");
            check($payload['port'] === $port, "$name: wrong port");
            check($payload['platform'] === $platform, "$name: wrong platform");
            foreach (['token', 'hash', 'extension', 'dtmf', 'callerId', 'flatId', 'domophoneId', 'flatNumber', 'bundle'] as $key) {
                check($payload[$key] === $params[$key], "$name: changed $key");
            }
            check($payload['type'] === $params['tokenType'], "$name: token type changed");
            check($payload['ttl'] === 30, "$name: TTL changed");
            check($payload['title'] === 'sip.incomingTitle', "$name: title changed");
            check($payload['timestamp'] >= $before && $payload['timestamp'] <= time(), "$name: invalid timestamp");
            check($sip->extension === $params['extension'], "$name: wrong extension lookup");
            check($sip->stunExtension === $params['extension'], "$name: wrong STUN lookup");
            check(($payload['stun'] ?? false) === $stun, "$name: STUN changed");
            check($stun || !array_key_exists('stun', $payload), "$name: unexpected STUN field");
            $runs++;
        }
    }
}
echo "PASS: $runs mobile SIP push cases\n";
