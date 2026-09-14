<?php
// Policy regression tests: no server config, network, push or physical relay I/O.
require_once __DIR__ . '/../../server/virtual-intercom/Service.php';
require_once __DIR__ . '/../../server/virtual-intercom/PanelRepository.php';

final class MemoryRedis {
    public array $data = [];
    public function get($key) { return $this->data[$key] ?? false; }
    public function setex($key, $ttl, $value) { $this->data[$key] = $value; return true; }
    public function set($key, $value, $options) { if (isset($this->data[$key])) return false; $this->data[$key] = $value; return true; }
    public function del(...$keys) { foreach ($keys as $key) unset($this->data[$key]); }
    public function eval($script, $args, $keys) {
        if (str_contains($script, 'DECR')) return isset($this->data[$args[0]]) ? --$this->data[$args[0]] : 0;
        if (str_contains($script, 'SETEX')) {
            if (($this->data[$args[0]] ?? null) !== $args[2]) return 0;
            $this->data[$args[1]] = $args[4]; return 1;
        }
        if (str_contains($script, 'INCR')) return $this->data[$args[0]] = ($this->data[$args[0]] ?? 0) + 1;
        if (($this->data[$args[0]] ?? null) === $args[1]) unset($this->data[$args[0]]);
        return 1;
    }
}
final class FakeStatement {
    public function execute($params) {}
    public function fetchAll($mode) { return [['house_flat_id' => 10, 'apartment' => 100, 'flat' => '100']]; }
}
final class FakeDB { public function prepare($sql) { return new FakeStatement(); } }
final class Houses {
    public bool $blocked = false, $member = true, $deviceEnabled = true;
    public int $output = 1;
    public array $deviceIds = [40];
    public array $deviceSubscribers = [];
    public int $deviceQueries = 0, $membershipQueries = 0;
    public $onAccess;
    public function getEntrance($id) { return ['entranceId' => 20, 'entrance' => 'Test', 'domophoneId' => 30, 'domophoneOutput' => $this->output]; }
    public function getFlat($id) { return ['autoBlock' => $this->blocked, 'entrances' => $id === 10 ? [['entranceId' => 20]] : []]; }
    public function getDomophone($id) { return ['enabled' => 1, 'model' => 'dks.json', 'url' => 'https://device.invalid', 'credentials' => 'fixture']; }
    public function getDevices($by, $id) { if ($this->onAccess) ($this->onAccess)(); $this->deviceQueries++; return array_map(fn($deviceId) => ['deviceId' => $deviceId, 'subscriberId' => $this->deviceSubscribers[$deviceId] ?? 50, 'platform' => 1, 'voipEnabled' => (int)$this->deviceEnabled,
        'tokenType' => 1, 'voipToken' => 'fixture', 'pushToken' => null, 'flats' => [['flatId' => 10, 'voipEnabled' => 1]]], $this->deviceIds); }
    public function getSubscribers($by, $id, $options) { $this->membershipQueries++; return [['flats' => $this->member ? [['flatId' => 10]] : []]]; }
}
function loadBackend($name) { return $GLOBALS['testBackends'][$name] ?? false; }
function loadDevice($type, $model, $url, $password, $firstTime, $lazy) {
    check(!$firstTime && $lazy, 'Opening triggered provisioning');
    return new class { public function openLock($output) { ($GLOBALS['testOpen'])($output); } };
}
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
function denied(callable $operation, $message) {
    try { $operation(); } catch (RuntimeException $e) { check($e->getCode() >= 400, $message . ': unexpected error'); return; }
    throw new RuntimeException($message . ': request was allowed');
}
function fixture() {
    $GLOBALS['testOpen'] = static function() {};
    $redis = new MemoryRedis(); $houses = new Houses();
    $settings = ['enabled' => true, 'origin' => 'https://test.invalid', 'sipDomain' => 'test.invalid', 'ws' => 'wss://test.invalid/wss'];
    $panels = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $panels->exec('CREATE TABLE houses_entrances (house_entrance_id INTEGER PRIMARY KEY)');
    $panels->exec('INSERT INTO houses_entrances VALUES (20)');
    $panels->exec('CREATE TABLE custom_fields_values (apply_to TEXT, id INTEGER, field TEXT, value TEXT)');
    $q = $panels->prepare("INSERT INTO custom_fields_values VALUES ('entrance',20,:field,:value)");
    $q->execute(['field' => 'virtualIntercom', 'value' => json_encode(['enabled' => true])]);
    $q->execute(['field' => 'virtualIntercomSlug', 'value' => 'fixturePanel']);
    return [new \VirtualIntercom\Service($redis, new FakeDB(), $houses, $settings, new \VirtualIntercom\PanelRepository($panels)), $redis, $houses];
}
function started() {
    [$s, $r, $h] = fixture();
    $guest = $s->create('fixturePanel', 10, '127.0.0.1');
    $id = $guest['id'];
    $s->internal('begin', ['endpoint' => 'vi_' . $id, 'uniqueid' => 'guest-channel']);
    $s->internal('legs', ['id' => $id, 'legs' => [['extension' => '2000000001', 'deviceId' => 40]]]);
    $p = ['id' => $id, 'extension' => '2000000001', 'channel' => 'PJSIP/2000000001-0001', 'uniqueid' => 'resident-channel'];
    $s->internal('bind', $p);
    return [$s, $r, $h, $guest, $p];
}
$tests = [
    'connection settings reuse the standard server and browser configs' => function() {
        $resolve = new ReflectionMethod(\VirtualIntercom\Service::class, 'connectionSettings');
        $client = ['asterisk' => ['ws' => 'wss://sip.invalid/socket', 'sipDomain' => 'pbx.invalid',
            'ice' => [['urls' => ['stun:ice.invalid']]]]];
        $config = ['api' => ['frontend' => 'https://RBT.invalid:8443/frontend']];
        $settings = $resolve->invoke(null, $config, $client);
        check($settings['enabled'] && $settings['origin'] === 'https://rbt.invalid:8443', 'Wrong configured origin or availability');
        check($settings['ws'] === $client['asterisk']['ws'] && $settings['sipDomain'] === 'pbx.invalid' &&
            $settings['iceServers'] === $client['asterisk']['ice'], 'Existing browser connection settings changed');
        $config['api']['frontend'] = 'https://rbt.invalid:443/frontend';
        check($resolve->invoke(null, $config, $client)['origin'] === 'https://rbt.invalid', 'Default HTTPS port not normalized');
        check(!$resolve->invoke(null, $config, [])['enabled'], 'Missing WebRTC config enabled calls');
        $client['asterisk']['ws'] = 'ws://sip.invalid/socket';
        check(!$resolve->invoke(null, $config, $client)['enabled'], 'Insecure WebSocket enabled calls');
        $config['api']['frontend'] = 'http://rbt.invalid/frontend';
        check(!$resolve->invoke(null, $config, $client)['enabled'], 'Insecure origin enabled calls');
    },
    'guest receives the existing browser ICE settings unchanged' => function() {
        $resolve = new ReflectionMethod(\VirtualIntercom\Service::class, 'connectionSettings');
        foreach ([[], [['urls' => ['stun:ice.invalid']]]] as $ice) {
            [$s] = fixture();
            $settings = $resolve->invoke(null, ['api' => ['frontend' => 'https://test.invalid/frontend']],
                ['asterisk' => ['ws' => 'wss://test.invalid/wss', 'sipDomain' => 'test.invalid', 'ice' => $ice]]);
            (new ReflectionProperty($s, 'settings'))->setValue($s, $settings);
            $g = $s->create('fixturePanel', 10, '127.0.0.1');
            check($g['sip']['iceServers'] === $ice, 'Browser ICE settings changed');
        }
    },
    'guest sees no resident credential or door target' => function() {
        [$s] = fixture(); $g = $s->create('fixturePanel', 10, '127.0.0.1');
        check(!array_intersect(['previewHash', 'domophoneId', 'output', 'devices', 'deviceIds'], array_keys($g)), 'Private call data leaked');
        check($s->endpoint($g['sip']['username'], 'endpoints')['context'] === 'virtual-intercom', 'Wrong guest context');
        check($s->endpoint($g['sip']['username'], 'endpoints')['allow_transfer'] === 'no', 'Guest transfer enabled');
    },
    'unrelated flat denied' => function() { [$s] = fixture(); denied(fn() => $s->create('fixturePanel', 99, '127.0.0.1'), 'Unrelated flat'); },
    'all current apartment members receive the call' => function() {
        [$s,$r,$h] = fixture(); $h->deviceIds = [40, 41]; $h->deviceSubscribers[41] = 51;
        $g = $s->create('fixturePanel', 10, '127.0.0.1');
        $begin = $s->internal('begin', ['endpoint' => $g['sip']['username'], 'uniqueid' => 'guest']);
        check(array_column($begin['devices'], 'subscriberId') === [50, 51], 'Apartment subscriber was omitted');
    },
    'answer stops pushes to every device including unanswered legs' => function() {
        [$s,$r,$h] = fixture(); $h->deviceIds = [40, 41];
        $g = $s->create('fixturePanel', 10, '127.0.0.1');
        $s->internal('begin', ['endpoint' => $g['sip']['username'], 'uniqueid' => 'guest']);
        $s->internal('legs', ['id' => $g['id'], 'legs' => [
            ['extension' => '2000000001', 'deviceId' => 40],
            ['extension' => '2000000002', 'deviceId' => 41],
        ]]);
        $winner = ['id' => $g['id'], 'extension' => '2000000001', 'channel' => 'PJSIP/2000000001-1', 'uniqueid' => 'resident'];
        $s->internal('bind', $winner);
        $other = ['id' => $g['id'], 'extension' => '2000000002'];
        check($s->internal('push', $other)['ok'], 'Ringing call stopped notifying the other device');
        $s->internal('answer', $winner);
        denied(fn() => $s->internal('push', $other), 'Other device notified after answer');
        denied(fn() => $s->internal('push', $winner), 'Winner notified after answer');
    },
    'wrong origin denied' => function() { [$s] = fixture(); denied(fn() => $s->checkOrigin('https://other.invalid'), 'Origin'); },
    'wrong bearer denied' => function() { [$s,,, $g] = started(); denied(fn() => $s->status($g['id'], 'wrong'), 'Bearer'); },
    'open before answer denied' => function() { [$s,,,, $p] = started(); denied(fn() => $s->internal('open', $p), 'Unanswered'); },
    'visitor leg denied' => function() { [$s,,,, $p] = started(); $s->internal('answer', $p); $p['uniqueid'] = 'guest-channel'; denied(fn() => $s->internal('open', $p), 'Visitor'); },
    'unknown mobile leg denied' => function() { [$s,,,, $p] = started(); $p['extension'] = '2000000099'; denied(fn() => $s->internal('bind', $p), 'Unknown extension'); },
    'begin reuses freshly checked devices only in the internal response' => function() {
        [$s,$r,$h] = fixture(); $g = $s->create('fixturePanel', 10, '127.0.0.1');
        $h->deviceIds = [40, 41]; $h->deviceQueries = 0;
        $result = $s->internal('begin', ['endpoint' => 'vi_' . $g['id'], 'uniqueid' => 'guest']);
        check($h->deviceQueries === 1 && count($result['devices']) === 2, 'Fresh devices not reused');
        check($result['deviceIds'] === [40], 'Session device allowlist widened');
        check(!isset($s->status($g['id'], $g['token'])['devices']), 'Push destinations leaked to visitor');
    },
    'batch prepares all devices with one fresh membership check' => function() {
        [$s,$r,$h] = fixture(); $h->deviceIds = [40, 41];
        $g = $s->create('fixturePanel', 10, '127.0.0.1');
        $s->internal('begin', ['endpoint' => 'vi_' . $g['id'], 'uniqueid' => 'guest']);
        $h->deviceQueries = $h->membershipQueries = 0;
        $s->internal('legs', ['id' => $g['id'], 'legs' => [
            ['extension' => '2000000001', 'deviceId' => 40], ['extension' => '2000000002', 'deviceId' => 41]]]);
        check($h->deviceQueries === 1 && $h->membershipQueries === 1, 'Device preparation repeats access queries');
        check($r->get('VI:MOBILE:2000000001') === $g['id'] && $r->get('VI:MOBILE:2000000002') === $g['id'], 'Incomplete batch');
    },
    'invalid batch grants no partial credentials' => function() {
        [$s,$r,, $g] = started();
        denied(fn() => $s->internal('legs', ['id' => $g['id'], 'legs' => [
            ['extension' => '2000000002', 'deviceId' => 40], ['extension' => '2000000003', 'deviceId' => 99]]]), 'Foreign device');
        check(!$r->get('VI:AUTH:2000000002') && !$r->get('VI:MOBILE:2000000002'), 'Partial batch authorized');
    },
    'batch checks membership again after begin' => function() {
        [$s,$r,$h,$g] = started(); $h->member = false;
        denied(fn() => $s->internal('legs', ['id' => $g['id'], 'legs' => [['extension' => '2000000002', 'deviceId' => 40]]]), 'Revoked member');
        check(!$r->get('VI:AUTH:2000000002'), 'Revoked member authorized');
    },
    'resident action idempotent and output fixed' => function() {
        [$s,$r,, $g,$p] = started(); $s->internal('answer', $p);
        check($s->internal('open', $p)['status'] === 'sent', 'Driver result');
        check($s->internal('open', $p)['duplicate'] === true, 'Duplicate');
        $saved = json_decode($r->get('VI:SESSION:' . $g['id']), true);
        check($saved['output'] === 1, 'Wrong relay');
    },
    'cancelled call cannot open or register' => function() { [$s,,, $g,$p] = started(); $s->internal('answer', $p); $s->cancel($g['id'], $g['token']); denied(fn() => $s->internal('open', $p), 'Cancelled'); check($s->endpoint($g['sip']['username'], 'auths') === false, 'Cancelled SIP credential'); },
    'late resident registration does not regain call capability' => function() {
        [$s,$r,, $g,$p] = started(); $s->internal('answer', $p); $s->cancel($g['id'], $g['token']);
        $endpoint = $s->mobileEndpoint($p['extension'], 'endpoints');
        check($endpoint['context'] === 'virtual-intercom-resident' && $endpoint['allow_transfer'] === 'no', 'Late resident may originate calls');
        check($s->mobileEndpoint($p['extension'], 'auths')['password'] === $r->get('VI:AUTH:' . $p['extension']), 'Late authentication lost');
        denied(fn() => $s->internal('open', $p), 'Late opening');
        $r->del('VI:SESSION:' . $g['id']);
        check($s->mobileEndpoint($p['extension'], 'auths') !== false, 'Cleanup requires expired session');
        denied(fn() => $s->internal('answer', $p), 'Expired call revived');
        $r->del('VI:AUTH:' . $p['extension']);
        check($s->mobileEndpoint($p['extension'], 'auths') === false, 'Grace expiry ignored');
    },
    'expired session denied' => function() { [$s,$r,, $g,$p] = started(); $s->internal('answer', $p); $v = json_decode($r->get('VI:SESSION:' . $g['id']), true); $v['expires'] = time() - 1; $r->data['VI:SESSION:' . $g['id']] = json_encode($v); denied(fn() => $s->internal('open', $p), 'Expired'); },
    'membership revocation checked again at opening' => function() { [$s,,$h,,$p] = started(); $s->internal('answer', $p); $h->member = false; denied(fn() => $s->internal('open', $p), 'Revoked member'); },
    'block checked again at opening' => function() { [$s,,$h,,$p] = started(); $s->internal('answer', $p); $h->blocked = true; denied(fn() => $s->internal('open', $p), 'Blocked flat'); },
    'reassigned relay invalidates session' => function() { [$s,,$h,,$p] = started(); $s->internal('answer', $p); $h->output = 0; denied(fn() => $s->internal('open', $p), 'Reassigned relay'); },
    'second answer cannot replace winner' => function() { [$s,,,, $p] = started(); $s->internal('answer', $p); denied(fn() => $s->internal('answer', $p), 'Winner replacement'); },
    'visitor cannot supply begin session id' => function() { [$s] = fixture(); denied(fn() => $s->internal('begin', ['id' => str_repeat('a', 32), 'endpoint' => '100001']), 'Untrusted endpoint'); },
    'slow opening cannot resurrect a cancelled call or repeat the relay' => function() {
        [$s,$r,, $g,$p] = started(); $s->internal('answer', $p); $count = 0;
        $GLOBALS['testOpen'] = function($output) use($s,$g,$p,&$count) {
            check($output === 1, 'Wrong physical output'); $count++;
            check($s->internal('open', $p)['status'] === 'sending', 'Concurrent opening was not reserved');
            $s->cancel($g['id'], $g['token']);
        };
        check($s->internal('open', $p)['status'] === 'sent', 'Driver result lost');
        $state = $s->status($g['id'], $g['token']);
        check($state['status'] === 'cancelled' && $state['doorStatus'] === 'sent' && $count === 1, 'Slow I/O overwrote cancellation or repeated opening');
    },
    'failed driver remains a single failed command' => function() {
        [$s,,,, $p] = started(); $s->internal('answer', $p); $count = 0;
        $GLOBALS['testOpen'] = function() use(&$count) { $count++; throw new RuntimeException('Device timeout'); };
        check($s->internal('open', $p)['status'] === 'error', 'Driver failure reported as opened');
        check($s->internal('open', $p)['duplicate'] && $count === 1, 'Uncertain operation repeated');
    },
    'expired mutation lock cannot overwrite a newer call state' => function() {
        [$s,$r,$h,$g,$p] = started();
        $h->onAccess = function() use($r,$g) {
            $key = 'VI:SESSION:' . $g['id']; $state = json_decode($r->data[$key], true);
            $state['status'] = 'cancelled'; $r->data[$key] = json_encode($state);
            $r->data['VI:LOCK:' . $g['id']] = 'new-owner';
        };
        denied(fn() => $s->internal('answer', $p), 'Expired lock');
        check($s->status($g['id'], $g['token'])['status'] === 'cancelled', 'Stale operation overwrote cancellation');
        check($r->data['VI:LOCK:' . $g['id']] === 'new-owner', 'Stale operation released a newer lock');
    },
    'notification frames use the native storage and are cleared on hangup' => function() {
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAgAAAQABAAD//gAQTGF2YzYyLjExLjEwMAD/2wBDAAgEBAQEBAUFBQUFBQYGBgYGBgYGBgYGBgYHBwcICAgHBwcGBgcHCAgICAkJCQgICAgJCQoKCgwMCwsODg4RERT/xABMAAEBAAAAAAAAAAAAAAAAAAAABwEBAQAAAAAAAAAAAAAAAAAABQcQAQAAAAAAAAAAAAAAAAAAAAARAQAAAAAAAAAAAAAAAAAAAAD/wAARCAAQABADASIAAhEAAxEA/9oADAMBAAIRAxEAPwCOAL+Kf//Z');
        foreach ([false, true] as $useMemfs) {
            $memfs = new class { public array $files = []; public function putFile($hash, $data) { $this->files[$hash] = $data; } };
            $GLOBALS['testBackends']['memfs'] = $useMemfs ? $memfs : false;
            [$s,$r,, $g,$p] = started();
            $hash = json_decode($r->get('VI:SESSION:' . $g['id']), true)['previewHash'];
            $s->frame($g['id'], $g['token'], $jpeg);
            check(($useMemfs ? $memfs->files[$hash] : $r->get('shot_' . $hash)) === $jpeg, 'Native camshot cannot read the frame');
            check($r->get('live_' . $hash) === $jpeg, 'Live preview frame missing');
            $s->cancel($g['id'], $g['token']);
            check(!$r->get('shot_' . $hash) && !$r->get('live_' . $hash), 'Preview survived cancellation');
            if ($useMemfs) check($memfs->files[$hash] === '', 'Memfs frame survived cancellation');
            denied(fn() => $s->frame($g['id'], $g['token'], $jpeg), 'Late frame restored preview');
        }
        unset($GLOBALS['testBackends']['memfs']);
    },
    'invalid image rejected' => function() { [$s,,, $g] = started(); denied(fn() => $s->frame($g['id'], $g['token'], 'not-jpeg'), 'Image validation'); },
];
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    foreach ($tests as $name => $test) { $test(); echo "PASS $name\n"; }
    echo count($tests) . " policy tests passed; physical commands: 0\n";
}
