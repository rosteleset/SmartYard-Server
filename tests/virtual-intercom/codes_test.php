<?php
// Real SQL policy checks, isolated Redis and device fixtures. No physical I/O.
require_once __DIR__ . '/service_test.php';

function codesFixture(): array {
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('CREATE TABLE houses_entrances (house_entrance_id INTEGER PRIMARY KEY)');
    $db->exec('INSERT INTO houses_entrances VALUES (20), (21)');
    $db->exec('CREATE TABLE houses_flats (house_flat_id INTEGER PRIMARY KEY, flat TEXT, open_code TEXT, manual_block INTEGER, admin_block INTEGER, auto_block INTEGER)');
    $db->exec("INSERT INTO houses_flats VALUES (10, '100', '12345', 0, 0, 0), (11, '101', '23456', 0, 0, 0)");
    $db->exec('CREATE TABLE houses_entrances_flats (house_entrance_id INTEGER, house_flat_id INTEGER, apartment INTEGER)');
    $db->exec('INSERT INTO houses_entrances_flats VALUES (20, 10, 100), (21, 11, 101)');
    $db->exec('CREATE TABLE custom_fields_values (apply_to TEXT, id INTEGER, field TEXT, value TEXT)');
    $q = $db->prepare("INSERT INTO custom_fields_values VALUES ('entrance', 20, :field, :value)");
    $q->execute(['field' => 'virtualIntercom', 'value' => json_encode(['enabled' => true, 'allowAllFlats' => false])]);
    $q->execute(['field' => 'virtualIntercomSlug', 'value' => 'fixturePanel']);
    $houses = new class {
        public bool $enabled = true;
        public $output = 2;
        public string $model = 'dks.json';
        public array $events = [];
        public function getEntrance($id) { return ['domophoneId' => 30, 'domophoneOutput' => $this->output]; }
        public function getDomophone($id) { return ['enabled' => $this->enabled, 'model' => $this->model, 'url' => 'https://device.invalid', 'credentials' => 'fixture']; }
        public function paranoidEvent(...$args) { $this->events[] = $args; }
        public function getDevices(...$args) { throw new LogicException('Code opening must not prepare calls'); }
    };
    $redis = new MemoryRedis();
    $GLOBALS['testBackends'] = [];
    $GLOBALS['unexpectedOpens'] = 0;
    $GLOBALS['testOpen'] = static function() { $GLOBALS['unexpectedOpens']++; throw new LogicException('Unexpected relay command'); };
    $service = new \VirtualIntercom\Service($redis, $db, $houses, ['enabled' => true], new \VirtualIntercom\PanelRepository($db));
    return [$service, $redis, $db, $houses];
}

function codeDenied(callable $operation, string $message): void {
    denied($operation, $message);
    check($GLOBALS['unexpectedOpens'] === 0, $message . ': reached the physical driver');
}

foreach (['23456', '99999', '', '00000', '10000', '012345', '12345x', '1234', "12345\n", "12345' OR 1=1--"] as $code) {
    [$s, $r, $db, $h] = codesFixture();
    codeDenied(fn() => $s->openByCode('fixturePanel', $code, '192.0.2.1'), 'Foreign, missing or invalid code');
}
echo "PASS unknown codes, cross-entrance isolation, disabled code values and malformed input\n";

foreach (['manual_block', 'admin_block', 'auto_block'] as $column) {
    [$s, $r, $db] = codesFixture();
    $db->exec("UPDATE houses_flats SET $column=1 WHERE house_flat_id=10");
    codeDenied(fn() => $s->openByCode('fixturePanel', '12345', '192.0.2.1'), 'Blocked apartment');
}
foreach (['disabled', 'dummy', 'missing-output', 'unlinked', 'panel-disabled', 'reset-code'] as $condition) {
    [$s, $r, $db, $h] = codesFixture();
    if ($condition === 'disabled') $h->enabled = false;
    if ($condition === 'dummy') $h->model = 'dummy.json';
    if ($condition === 'missing-output') $h->output = null;
    if ($condition === 'unlinked') $db->exec('DELETE FROM houses_entrances_flats WHERE house_entrance_id=20');
    if ($condition === 'panel-disabled') $db->exec("UPDATE custom_fields_values SET value='{}' WHERE field='virtualIntercom'");
    if ($condition === 'reset-code') $db->exec("UPDATE houses_flats SET open_code='34567' WHERE house_flat_id=10");
    codeDenied(fn() => $s->openByCode('fixturePanel', '12345', '192.0.2.1'), $condition);
}
echo "PASS all apartment blocks, disabled/missing devices, unlinked flats, panel disable and immediate code reset\n";

[$s, $r, $db, $h] = codesFixture();
$sent = [];
$GLOBALS['testOpen'] = static function($output) use (&$sent) { $sent[] = $output; };
$plog = new class {
    const EVENT_OPENED_BY_CODE = 6;
    public array $events = [];
    public function addDoorOpenDataById(...$args) { $this->events[] = $args; }
};
$GLOBALS['testBackends']['plog'] = $plog;
$db->exec('UPDATE houses_flats SET manual_block=NULL, admin_block=NULL, auto_block=NULL WHERE house_flat_id=10');
check($s->openByCode('fixturePanel', '12345', '192.0.2.1') === ['doorStatus' => 'sent'], 'Missing success');
check($sent === [2], 'Wrong physical relay or repeated command');
check($h->events === [[20, 'code', '12345']], 'Wrong event attribution');
check(array_slice($plog->events[0], 1) === [30, 6, 2, '12345'], 'Not recorded as a native code opening');
denied(fn() => $s->openByCode('fixturePanel', '12345', '192.0.2.2'), 'Duplicate opening');
check($sent === [2], 'Concurrent browser sent another relay command');
check(!array_filter(array_keys($r->data), fn($key) => str_starts_with($key, 'VI:SESSION:')), 'Opening created a SIP session');
for ($i = 0; $i < 25; $i++) {
    $r->del('VI:CODE:DOOR:30:2'); // Simulate expiry of the relay cooldown.
    $s->openByCode('fixturePanel', '12345', '192.0.2.1');
}
check(count($sent) === 26, 'Correct codes consumed the failed-guess budget');
echo "PASS configured relay, native audit, call opt-in independence, no mobile devices, duplicate protection and successful-attempt refunds\n";

foreach (['ip', 'entrance'] as $scope) {
    [$s, $r, $db, $h] = codesFixture();
    $attempts = $scope === 'ip' ? 5 : 20;
    for ($i = 0; $i < $attempts; $i++) {
        $ip = $scope === 'ip' ? '192.0.2.1' : '192.0.2.' . ($i + 1);
        codeDenied(fn() => $s->openByCode('fixturePanel', '99999', $ip), 'Invalid code accepted');
    }
    try {
        $s->openByCode('fixturePanel', '12345', $scope === 'ip' ? '192.0.2.1' : '192.0.2.99');
        throw new LogicException('Rate limit bypass');
    } catch (RuntimeException $e) { check($e->getCode() === 429, 'Wrong limiter status'); }
}
echo "PASS per-IP and cross-IP entrance guess limits\n";

[$s, $r, $db, $h] = codesFixture();
$failedRedis = new class { public function eval(...$args) { return false; } };
$s = new \VirtualIntercom\Service($failedRedis, $db, $h, ['enabled' => true], new \VirtualIntercom\PanelRepository($db));
codeDenied(fn() => $s->openByCode('fixturePanel', '12345', '192.0.2.1'), 'Failed rate limiter');
echo "PASS rate limiter failure cannot bypass authorization\n";

[$s, $r, $db, $h] = codesFixture();
$h->output = 0; $sent = [];
$GLOBALS['testOpen'] = static function($output) use (&$sent) { $sent[] = $output; };
$s->openByCode('fixturePanel', '12345', '192.0.2.1');
check($sent === [0], 'Relay zero was treated as missing');
echo "PASS zero is a valid physical relay\n";

[$s, $r, $db, $h] = codesFixture();
$sent = 0;
$GLOBALS['testOpen'] = static function() use (&$sent) { $sent++; throw new RuntimeException('Device timeout'); };
denied(fn() => $s->openByCode('fixturePanel', '12345', '192.0.2.1'), 'Device timeout reported as success');
denied(fn() => $s->openByCode('fixturePanel', '12345', '192.0.2.1'), 'Retry after uncertain command');
check($sent === 1 && !$h->events, 'Failed command retried or logged as success');
echo "PASS device failure cannot show success or bypass the relay cooldown\n";
