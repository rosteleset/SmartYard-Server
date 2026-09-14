<?php
// Execute the actual push dispatcher with in-memory services and a recording provider.
// Optional argument: a previous checkout for payload compatibility checks.
require_once __DIR__ . '/service_test.php';
require_once __DIR__ . '/../../server/virtual-intercom/Asterisk.php';
function i18n($key) { return $key; }
function json5_decode($source, $assoc) { return []; }

function pushDispatcher(string $file): string {
    $source = file_get_contents($file);
    $start = strpos($source, '                case "push":');
    $end = strpos($source, '                case "concierge":', $start);
    check($start !== false && $end !== false, 'Push dispatcher not found');
    return 'switch ("push") {' . substr($source, $start, $end - $start) . '}';
}
function deliver(array $params, string $source): array {
    $provider = $GLOBALS['testBackends']['isdn'];
    $provider->sent = [];
    eval($source);
    return $provider->sent;
}
$current = pushDispatcher(__DIR__ . '/../../server/asterisk.php');
$previous = null;
if (isset($argv[1])) {
    $adapter = file_get_contents($argv[1] . '/server/virtual-intercom/Asterisk.php');
    $adapter = str_replace(['<?php', "require_once __DIR__ . '/Service.php';", 'final class Asterisk'],
        ['', '', 'final class PreviousAsterisk'], $adapter);
    eval($adapter);
    $previous = str_replace('\\VirtualIntercom\\Asterisk::', '\\VirtualIntercom\\PreviousAsterisk::',
        pushDispatcher($argv[1] . '/server/asterisk.php'));
}
[$service, $redis, , $guest, $leg] = started();
$db = new FakeDB();
$config = ['api' => ['frontend' => 'https://test.invalid']];
$GLOBALS['testBackends']['isdn'] = new class {
    public array $sent = [];
    public function push($params) { $this->sent[] = $params; }
};
$GLOBALS['testBackends']['sip'] = $sip = new class {
    public int $port = 0;
    public $stun = 'stun:fixture.invalid';
    public function server($by, $extension) { check($by === 'extension', 'Wrong SIP lookup'); return ['ip' => 'sip.invalid', 'sip_tcp_port' => $this->port]; }
    public function stun($extension) { return $this->stun; }
};
$GLOBALS['testBackends']['households'] = $houses = new class {
    public int $reads = 0;
    public function getDomophone($id) { $this->reads++; return ['video' => 'external']; }
    public function getEntrances($by, $filter) { return [['cameraId' => 40]]; }
};
$GLOBALS['testBackends']['cameras'] = new class {
    public function getCamera($id) { return ['dvrStream' => 'physical-camera']; }
};
$GLOBALS['testBackends']['dvr'] = new class {
    public function getDVRServerForCam($camera) { return ['type' => 'fixture-dvr']; }
    public function getDVRTokenForCam($camera, $ttl) { return 'fixture-video-token'; }
    public function getDVRStreamURLForCam($camera) { return 'https://camera.invalid/stream'; }
};
$params = ['token' => 'fixture-push-token', 'tokenType' => 1, 'extension' => '2000000001',
    'hash' => 'physical-preview', 'dtmf' => '9', 'platform' => 1, 'callerId' => 'Fixture',
    'flatId' => 10, 'flatNumber' => '12', 'domophoneId' => 20];
$preview = $service->internal('push', $leg)['hash'];
$count = 0;
foreach ([false, true] as $virtual) {
    foreach ([1, 2] as $platform) {
        foreach ([0, 5070] as $port) {
            $sip->port = $port; $sip->stun = $port ? null : 'stun:fixture.invalid';
            $input = $params;
            $input['extension'] = $virtual ? $leg['extension'] : '2000000099';
            $input['platform'] = $platform;
            if ($port) $input['bundle'] = 'custom';
            $houses->reads = 0;
            $sent = deliver($input, $current);
            check(count($sent) === 1, 'Push was dropped or duplicated');
            $push = $sent[0];
            check($push['port'] === ($port ?: 5060) && $push['platform'] === ($platform === 1 ? 'ios' : 'android'), 'Transport or platform changed');
            check(isset($push['stun']) === !$port && $push['ttl'] === 30, 'STUN or TTL changed');
            if ($virtual) {
                check($houses->reads === 0 && $push['hash'] === $preview, 'Virtual push used physical camera settings');
                check($push['videoType'] === 'inband' && $push['dtmf'] === '5' && $push['dtmfProtocol'] === 'info', 'Virtual media settings lost');
                check(!isset($push['videoStream'], $push['videoToken']) && $push['bundle'] === ($port ? 'custom' : 'default'), 'Virtual payload changed');
            } else {
                check($houses->reads === 1 && $push['hash'] === 'physical-preview' && $push['dtmf'] === '9', 'Ordinary push changed');
                check($push['videoType'] === 'external' && $push['videoStream'] === 'https://camera.invalid/stream' && !isset($push['dtmfProtocol']), 'Physical video changed');
            }
            if ($previous) {
                $old = deliver($input + ($virtual ? ['virtualCallId' => $guest['id']] : []), $previous);
                unset($old[0]['timestamp'], $sent[0]['timestamp']);
                check($old == $sent, 'Previous provider payload differs');
            }
            $count++;
        }
    }
}
// Both initial and repeated notifications use the existing payload with no session ID.
$binding = 'VI:MOBILE:' . $leg['extension'];
$redis->data[$binding] = str_repeat('f', 32);
check(deliver($params, $current) === [], 'Unknown session fell through to ordinary push');
$redis->data[$binding] = $guest['id'];
$sessionKey = 'VI:SESSION:' . $guest['id'];
$savedSession = $redis->data[$sessionKey];
$expired = json_decode($savedSession, true); $expired['expires'] = time() - 1;
$redis->data[$sessionKey] = json_encode($expired);
check(deliver($params, $current) === [], 'Expired session sent a push');
$redis->data[$sessionKey] = $savedSession;
$redis->del($binding);
check(deliver($params, $current) === [], 'Expired binding with remaining SIP credentials became an ordinary call');
$redis->data[$binding] = $guest['id'];
$repeat = $params; $repeat['extension'] = (int)$repeat['extension']; $repeat['mobile'] = 'fixture*'; $repeat['uniq'] = 'fixture-unique';
check(count(deliver($repeat, $current)) === 1, 'Native repeat cannot resolve the virtual session');
$service->internal('answer', $leg);
check(deliver($params, $current) === [] && deliver($repeat, $current) === [], 'Answered call still sends initial or repeat pushes');
$service->cancel($guest['id'], $guest['token']);
check(deliver($params, $current) === [] && deliver($repeat, $current) === [], 'Cancelled session sent a push');
$redis->del($sessionKey);
check(deliver($params, $current) === [], 'Missing session sent a push');
$ordinary = $params; $ordinary['extension'] = '2000000099';
check(count(deliver($ordinary, $current)) === 1, 'Virtual rejection affected ordinary calls');
echo "PASS $count shared-provider payload cases, physical/virtual media isolation and late-push rejection; provider I/O: 0\n";
