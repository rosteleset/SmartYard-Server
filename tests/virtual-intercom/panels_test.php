<?php
require_once __DIR__ . '/service_test.php';
require_once __DIR__ . '/../../server/virtual-intercom/PanelRepository.php';
require_once __DIR__ . '/../../server/api/api.php';
require_once __DIR__ . '/../../server/api/houses/virtualIntercom.php';
require_once __DIR__ . '/../../server/backends/backend.php';
require_once __DIR__ . '/../../server/backends/customFields/customFields.php';
require_once __DIR__ . '/../../server/backends/customFields/internal/internal.php';

$db = new class('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]) extends PDO {
    public function modify($sql, $params = []) { $q = $this->prepare($sql); $q->execute($params); return $q->rowCount(); }
};
$db->exec('PRAGMA foreign_keys = ON');
$db->exec('CREATE TABLE houses_entrances (house_entrance_id INTEGER PRIMARY KEY)');
$db->exec('INSERT INTO houses_entrances VALUES (20), (21)');
$db->exec('CREATE TABLE houses_flats (house_flat_id INTEGER PRIMARY KEY, flat TEXT)');
$db->exec("INSERT INTO houses_flats VALUES (10, '100')");
$db->exec('CREATE TABLE houses_entrances_flats (house_entrance_id INTEGER, house_flat_id INTEGER, apartment INTEGER)');
$db->exec('INSERT INTO houses_entrances_flats VALUES (20, 10, 100), (21, 10, 100)');
$db->exec('CREATE TABLE custom_fields_values (custom_fields_value_id INTEGER PRIMARY KEY, apply_to TEXT, id INTEGER, field TEXT, value TEXT, UNIQUE(apply_to, id, field))');
$db->exec('CREATE TABLE custom_fields (custom_field_id INTEGER PRIMARY KEY, apply_to TEXT, catalog TEXT, type TEXT, field TEXT UNIQUE, field_display TEXT, field_description TEXT, editor TEXT, regex TEXT, "add" INTEGER, modify INTEGER, tab TEXT)');
$db->exec('CREATE TABLE custom_fields_options (custom_field_id INTEGER)');
$db->exec("INSERT INTO custom_fields (apply_to, field) VALUES ('entrance', 'unrelated'), ('flat', 'other-flat')");
$db->exec("INSERT INTO custom_fields_values (apply_to, id, field, value) VALUES ('entrance', 20, 'unrelated', 'keep'), ('flat', 20, 'virtualIntercom', 'keep-flat')");
// The normal update runner discovers v98 through install.json. No feature CLI.
require_once __DIR__ . '/../../server/data/install.php';
$db->exec("CREATE TABLE core_vars (var_name TEXT, var_value TEXT)");
$db->exec("INSERT INTO core_vars VALUES ('dbVersion', '97')");
$config = ['db' => ['dsn' => 'pgsql:fixture']]; $version = 97;
ob_start(); initDB(); ob_end_clean();
check($db->query("SELECT var_value FROM core_vars WHERE var_name = 'dbVersion'")->fetchColumn() === '98', 'Normal update skipped the feature');
check($db->query("SELECT COUNT(*) FROM custom_fields WHERE catalog = 'virtualIntercom'")->fetchColumn() == 4, 'Fields missing before the first panel');
ob_start(); initDB(); ob_end_clean();
check($db->query("SELECT COUNT(*) FROM custom_fields WHERE catalog = 'virtualIntercom'")->fetchColumn() == 4, 'Repeated update duplicated fields');
echo "PASS automatic v98 update, fields available before first panel and repeated update\n";
$repo = new \VirtualIntercom\PanelRepository($db);
$houses = new class {
    public $fixture;
    public bool $physicalEnabled = true;
    public function __construct() { $this->fixture = new Houses(); }
    public function getEntrance($id) { return in_array($id, [20, 21], true) ? ['entrance' => 'Entrance ' . $id, 'domophoneId' => 30, 'domophoneOutput' => 1] : false; }
    public function getDomophone($id) { return ['enabled' => (int)$this->physicalEnabled, 'model' => 'dks.json']; }
    public function getFlat($id) { return ['entrances' => $id === 10 ? [['entranceId'=>20], ['entranceId'=>21]] : ($id === 11 ? [['entranceId'=>20]] : [])]; }
    public function getDevices($by, $id) { return $this->fixture->getDevices($by, $id); }
    public function getSubscribers($by, $id, $options) { return $this->fixture->getSubscribers($by, $id, $options); }
    public function modifyEntrance(...$args) { throw new RuntimeException('Physical provisioning must not be called'); }
};
$redis = new MemoryRedis();
$settings = ['enabled' => true, 'origin' => 'https://test.invalid', 'sipDomain' => 'test.invalid', 'ws' => 'wss://test.invalid/wss'];
$s = new \VirtualIntercom\Service($redis, $db, $houses, $settings, $repo);
$values = ['enabled' => true, 'title' => 'Main entrance', 'subtitle' => '', 'listEnabled' => true, 'allowAllFlats' => true];

check($s->panelSettings(20)['url'] === null && !$s->panelSettings(20)['enabled'], 'Unsaved panel was published');
$saved = $s->savePanel(20, $values);
$slug = $repo->byEntrance(20)['slug'];
check($saved['url'] === 'https://test.invalid/v/' . $slug, 'Wrong public URL');
check(count($s->metadata($slug)['flats']) === 1, 'DB panel does not expose its entrance apartments');
$second = $s->savePanel(21, $values);
check($second['url'] !== $saved['url'], 'Entrances share the same panel');
$customFields = new \backends\customFields\internal(['backends' => ['customFields' => []]], $db, null, 'admin');
$GLOBALS['testBackends']['customFields'] = $customFields;
$customFields->cron('5min');
check($repo->bySlug($slug) !== null, 'Native five-minute cleanup deleted the panel');
check($db->query("SELECT COUNT(*) FROM custom_fields WHERE catalog = 'virtualIntercom'")->fetchColumn() == 4, 'Field definitions duplicated');
check($s->savePanel(20, $values + ['slug' => 'attacker', 'entranceId' => 21])['url'] === $saved['url'], 'Client moved the panel or changed its URL');
denied(fn() => $s->savePanel(99, $values), 'Unknown entrance');
denied(fn() => $s->savePanel(20, array_replace($values, ['enabled' => 'true'])), 'Nonboolean enabled');
denied(fn() => $s->savePanel(20, array_replace($values, ['title' => str_repeat('я', 121)])), 'Oversized title');
$houses->physicalEnabled = false;
denied(fn() => $s->savePanel(20, $values), 'Inactive physical panel');
$s->savePanel(20, array_replace($values, ['enabled' => false]));
denied(fn() => $s->metadata($slug), 'Disabled panel');
denied(fn() => $s->metadata('invalid-code'), 'Invalid short code');
$houses->physicalEnabled = true;
check($s->savePanel(20, $values)['url'] === $saved['url'], 'Re-enabling invalidated the QR code');

$g = $s->create($slug, 10, '127.0.0.1');
$s->internal('begin', ['endpoint' => $g['sip']['username'], 'uniqueid' => 'guest']);
$s->internal('legs', ['id' => $g['id'], 'legs' => [['extension' => '2000000001', 'deviceId' => 40]]]);
$p = ['id' => $g['id'], 'extension' => '2000000001', 'channel' => 'PJSIP/2000000001-fixture', 'uniqueid' => 'resident'];
$s->internal('bind', $p); $s->internal('answer', $p);
$s->savePanel(20, array_replace($values, ['enabled' => false]));
check($s->endpoint($g['sip']['username'], 'auths') === false, 'Disabled panel kept issuing visitor credentials');
denied(fn() => $s->internal('open', $p), 'Disabled panel allowed opening in an existing call');

check(preg_match('/^[a-zA-Z0-9_-]{12}$/D', $slug), 'Panel code is not short and URL-safe');
$s->savePanel(20, $values + ['unknown' => 'ignored']);
$stored = json_decode($db->query("SELECT value FROM custom_fields_values WHERE apply_to = 'entrance' AND id = 20 AND field = 'virtualIntercom'")->fetchColumn(), true);
check($stored === $values, 'API accepted unsupported settings');
$db->exec("INSERT INTO houses_flats VALUES (11, '101')");
$db->exec('INSERT INTO houses_entrances_flats VALUES (20, 11, 101)');
check(array_column($s->metadata($slug)['flats'], 'id') === [10, 11], 'New entrance apartment requires a separate allowlist');
check($repo->byEntrance(20)['slug'] === $slug, 'Saving changed the short link');

$nameField = $db->query("SELECT * FROM custom_fields WHERE field = 'virtualIntercomName'")->fetch(PDO::FETCH_ASSOC);
check($nameField['apply_to'] === 'flat' && $nameField['add'] == 1 && $nameField['modify'] == 1 && $nameField['tab'] === 'addresses.virtualIntercom', 'Apartment name is not editable in the native form');
$name = $db->prepare("INSERT INTO custom_fields_values (apply_to,id,field,value) VALUES ('flat',10,'virtualIntercomName',:value) ON CONFLICT(apply_to,id,field) DO UPDATE SET value=excluded.value");
$name->execute(['value' => "  Офис  Рога и Копыта  "]);
$db->exec("INSERT INTO custom_fields_values (apply_to,id,field,value) VALUES ('flat',10,'other-flat','private'), ('entrance',11,'virtualIntercomName','Wrong entity'), ('flat',99,'virtualIntercomName','Unrelated flat')");
$customFields->cron('5min');
check($s->metadata($slug)['flats'] === [
    ['id'=>10,'number'=>'100','name'=>'Офис Рога и Копыта'], ['id'=>11,'number'=>'101','name'=>'']
], 'Names changed routing, leaked other fields or failed to survive cleanup');
check($s->metadata($repo->byEntrance(21)['slug'])['flats'][0]['name'] === 'Офис Рога и Копыта', 'Apartment name differs across its entrances');
$name->execute(['value' => " \t\n\u{00a0} "]);
check($s->metadata($slug)['flats'][0]['name'] === '', 'Whitespace name should use the default title');
$name->execute(['value' => str_repeat('я', 140)]);
check(mb_strlen($s->metadata($slug)['flats'][0]['name']) === 120, 'Public apartment name is unbounded');
$db->exec("DELETE FROM custom_fields_values WHERE apply_to='flat' AND id=10 AND field='virtualIntercomName'");
check($s->metadata($slug)['flats'][0]['name'] === '', 'Cleared name did not restore the default');
echo "PASS public apartment names, shared entrances, unchanged numbers, whitespace, limits, clearing, privacy and native cleanup\n";

// The entrance policy controls both discovery and server-side call access.
$permission = $db->query("SELECT * FROM custom_fields WHERE field='virtualIntercomCallsEnabled'")->fetch(PDO::FETCH_ASSOC);
check($permission['apply_to']==='flat' && $permission['editor']==='noyes' && $permission['add']==1 && $permission['modify']==1, 'Apartment call permission is not editable');
$accessRedis = new MemoryRedis();
$accessService = new \VirtualIntercom\Service($accessRedis, $db, $houses, $settings, $repo);
$create = function($id=10) use($accessService, $accessRedis, $slug) {
    // Keep repeated policy cases independent of rate limiting, preserving calls.
    foreach(array_keys($accessRedis->data) as $key) if(str_starts_with($key,'VI:RATE:')) unset($accessRedis->data[$key]);
    return $accessService->create($slug, $id, '127.0.0.1');
};
$forbidden = function(callable $call, string $message) {
    try { $call(); } catch(RuntimeException $error) { check($error->getCode()===403, $message.': wrong error'); return; }
    throw new RuntimeException($message.': access granted');
};
check($s->panelSettings(20)['allowAllFlats']===true, 'Default must preserve all-apartment access');
$restricted = array_replace($values,['allowAllFlats'=>false]);
$s->savePanel(20,$restricted);
check($s->metadata($slug)['flats']===[], 'Missing opt-in exposed apartments');
$forbidden(fn()=>$create(), 'Direct call bypassed a missing opt-in');
$oldForm=$values;unset($oldForm['allowAllFlats']);
denied(fn()=>$s->savePanel(20,$oldForm), 'Incomplete settings');
foreach(['true',0,null] as $bad) denied(fn()=>$s->savePanel(20,array_replace($values,['allowAllFlats'=>$bad])), 'Nonboolean entrance policy');
$flag = $db->prepare("INSERT INTO custom_fields_values (apply_to,id,field,value) VALUES ('flat',10,'virtualIntercomCallsEnabled',:value) ON CONFLICT(apply_to,id,field) DO UPDATE SET value=excluded.value");
foreach(['0','','true','yes',' 1 '] as $off) {
    $flag->execute(['value'=>$off]);
    check($s->metadata($slug)['flats']===[], 'Implicit apartment opt-in');
    $forbidden(fn()=>$create(), 'Invalid opt-in accepted');
}
$db->exec("INSERT INTO custom_fields_values (apply_to,id,field,value) VALUES ('entrance',11,'virtualIntercomCallsEnabled','1'), ('flat',99,'virtualIntercomCallsEnabled','1')");
check($s->metadata($slug)['flats']===[], 'Permission for another entity leaked into the list');
$forbidden(fn()=>$create(99), 'Opt-in bypassed entrance membership');
$flag->execute(['value'=>'1']);
$customFields->cron('5min');
check(array_column($s->metadata($slug)['flats'],'id')===[10], 'Explicit opt-in missing or unrelated flat exposed');
$g=$create();
$accessService->internal('begin',['endpoint'=>$g['sip']['username'],'uniqueid'=>'guest-optin']);
$accessService->internal('legs',['id'=>$g['id'],'legs'=>[['extension'=>'2000000002','deviceId'=>40]]]);
$leg=['id'=>$g['id'],'extension'=>'2000000002','channel'=>'PJSIP/2000000002-fixture','uniqueid'=>'resident-optin'];
$accessService->internal('bind',$leg);$accessService->internal('answer',$leg);
$flag->execute(['value'=>'0']);
$forbidden(fn()=>$accessService->internal('open',$leg), 'Revoked permission allowed door opening');
check($accessService->status($g['id'],$g['token'])['doorStatus']==='idle', 'Revoked permission sent a door command');
$s->savePanel(20,array_replace($restricted,['listEnabled'=>false]));
check($s->metadata($slug)['flats']===[], 'Hiding the list removed permission filtering');
$forbidden(fn()=>$create(), 'Numeric call bypassed opt-in with hidden list');
$s->savePanel(20,$values);
check(array_column($s->metadata($slug)['flats'],'id')===[10,11], 'All-apartment mode did not override apartment flags');
$g=$create();
$s->savePanel(20,$restricted);
$forbidden(fn()=>$accessService->internal('begin',['endpoint'=>$g['sip']['username'],'uniqueid'=>'late-guest']), 'New policy was not checked before dialing');
$flag->execute(['value'=>'1']);
$s->savePanel(21,$restricted);
check(array_column($s->metadata($repo->byEntrance(21)['slug'])['flats'],'id')===[10], 'Apartment opt-in does not apply to its other restricted entrance');
$s->savePanel(20,$values);$s->savePanel(21,$values);
echo "PASS entrance all/opt-in policy, explicit apartment permission, direct-call denial, hidden list, incomplete settings, linked entrances and revocation before dial/open; physical commands: 0\n";

$db->exec('DELETE FROM houses_entrances WHERE house_entrance_id = 20');
check($repo->bySlug($slug) === null, 'Deleted entrance retained its public panel');
check($db->query("SELECT value FROM custom_fields_values WHERE apply_to = 'entrance' AND id = 20 AND field = 'unrelated'")->fetchColumn() === 'keep', 'Unrelated entrance field changed');
check($db->query("SELECT value FROM custom_fields_values WHERE apply_to = 'flat' AND id = 20 AND field = 'virtualIntercom'")->fetchColumn() === 'keep-flat', 'Custom field of another entity changed');
check(\api\houses\virtualIntercom::index() === ['GET' => '#same(addresses,house,GET)', 'PUT' => '#same(addresses,house,PUT)'], 'Management rights differ from house rights');
echo "PASS custom fields, stable URLs, validation, entrance binding, disable/re-enable, short URLs, all entrance apartments, deletion and permission inheritance; physical commands: 0\n";
