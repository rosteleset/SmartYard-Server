<?php
// Run with RBT_TEST_PG_DSN (and optional RBT_TEST_PG_USER/RBT_TEST_PG_PASSWORD).
// See doc/server/api/billing/subscriptions.md for the test command.
// Real endpoint, billing normalization, field definitions/options, and PostgreSQL persistence.
// All tables are temporary; no application tables or household records are modified.
require_once getenv('RBT_HTMLPURIFIER_AUTOLOAD') ?: __DIR__ . '/../server/vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
require_once __DIR__ . '/../server/utils/purifier.php';
require_once __DIR__ . '/../server/utils/functions.php';
require_once __DIR__ . '/../server/utils/PDOExt.php';
require_once __DIR__ . '/../server/backends/backend.php';
require_once __DIR__ . '/../server/backends/billing/billing.php';
require_once __DIR__ . '/../server/backends/customFields/customFields.php';
require_once __DIR__ . '/../server/backends/customFields/internal/internal.php';
require_once __DIR__ . '/../server/api/api.php';
require_once __DIR__ . '/../server/api/billing/subscriptions.php';

$redis_cache_ttl = 0;
$dsn = getenv('RBT_TEST_PG_DSN');
if (!$dsn) {
    fwrite(STDERR, "Set RBT_TEST_PG_DSN to a PostgreSQL test connection.\n");
    exit(2);
}
$db = new PDOExt($dsn, getenv('RBT_TEST_PG_USER') ?: null, getenv('RBT_TEST_PG_PASSWORD') ?: null);
$db->beginTransaction();
$db->exec('CREATE TEMPORARY TABLE custom_fields (
    custom_field_id SERIAL PRIMARY KEY, apply_to TEXT, catalog TEXT, type TEXT, field TEXT UNIQUE,
    field_display TEXT, field_description TEXT, regex TEXT, link TEXT, format TEXT, editor TEXT,
    indx INTEGER, search INTEGER, required INTEGER, magic_class TEXT, magic_function TEXT, magic_hint TEXT,
    "add" INTEGER, modify INTEGER, tab TEXT, weight INTEGER
)');
$db->exec('CREATE TEMPORARY TABLE custom_fields_options (
    custom_field_option_id SERIAL PRIMARY KEY, custom_field_id INTEGER, option TEXT, option_display TEXT, display_order INTEGER
)');
$db->exec('CREATE TEMPORARY TABLE custom_fields_values (
    custom_fields_value_id SERIAL PRIMARY KEY, apply_to TEXT, id INTEGER, field TEXT, value TEXT,
    UNIQUE(apply_to, id, field)
)');

function addField($name, $type = 'text', $editor = 'text', $format = null, $required = 0, $regex = null, $applyTo = 'flat') {
    global $db;
    $q = $db->prepare('INSERT INTO custom_fields (apply_to,catalog,type,field,editor,format,required,regex,"add",modify,weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 0)');
    $q->execute([$applyTo, 'billing', $type, $name, $editor, $format, $required, $regex]);
    return $db->lastInsertId();
}
function option($id, $value) {
    global $db;
    $q = $db->prepare('INSERT INTO custom_fields_options (custom_field_id,option,display_order) VALUES (?, ?, 0)');
    $q->execute([$id, $value]);
}
$branch = addField('branch', 'select', 'select');
option($branch, '1'); option($branch, '2');
addField('note'); addField('zero', 'text', 'number'); addField('flag', 'text', 'yesno');
addField('details', 'text', 'json'); addField('requiredValue', 'text', 'text', null, 1);
addField('code', 'text', 'text', null, 0, '^[A-Z]{2}$');
$tags = addField('tags', 'select', 'select', 'multiple');
option($tags, 'x'); option($tags, 'y');
addField('freeSelect', 'select', 'select', 'editable');
addField('houseOnly', 'text', 'text', null, 0, null, 'house');

$households = new class {
    public $flat = ['flatId' => 7, 'flat' => '3', 'houseUuid' => 'house-test', 'contract' => '1234', 'autoBlock' => 1];
    public $patches = [];
    public $duplicate = false;
    function getFlats($mode, $params) {
        if ($mode === 'contract') {
            if ($params['contract'] !== $this->flat['contract']) return [];
            return $this->duplicate ? [$this->flat, array_merge($this->flat, ['flatId' => 8])] : [$this->flat];
        }
        if ($mode === 'houseUuidFlat') {
            foreach ($params as $p) {
                if ($p['buildingUUID'] === $this->flat['houseUuid'] && $p['flatNumber'] === $this->flat['flat']) return [$this->flat];
            }
        }
        return [];
    }
    function modifyFlat($id, $patch) { $this->patches[] = $patch; $this->flat = array_merge($this->flat, $patch); return true; }
    function getFlat($id) { return $this->flat; }
    function getSubscribers($mode, $id, $options = []) { return [['mobile' => '79991234567']]; }
};
$customFields = new \backends\customFields\internal(['backends' => ['customFields' => []]], $db, null);
$billing = new class($db) extends \backends\billing\billing {
    function __construct($db) { $this->db = $db; }
    function getSubscriberAccountInfo($login, $password) { return false; }
    function getSubscriberAdditionalServices($login, $password, $agrmid) { return false; }
};
$failCustomFields = false;
function loadBackend($name) {
    global $billing, $households, $customFields, $failCustomFields;
    if ($name === 'customFields' && $failCustomFields) return false;
    return ['billing' => $billing, 'households' => $households, 'customFields' => $customFields][$name] ?? false;
}
$checks = 0;
function expect($condition, $message) {
    global $checks;
    $checks++;
    if (!$condition) throw new RuntimeException($message);
}
function sync($item) {
    $answer = \api\billing\subscriptions::POST(['subscribers' => [$item]]);
    return $answer[200]['subscriptions'];
}
function values() {
    global $customFields;
    return $customFields->getValues('flat', 7);
}

foreach ([1, 2, '1', '2'] as $value) {
    $r = sync(['subscriberID' => 1234, 'isActive' => true, 'branch' => $value]);
    expect($r['updated'] === 1 && !$r['invalid'] && !$r['failed'], 'branch accepted');
    expect(values()['branch'] === (string)$value, 'branch persisted');
}
$r = sync(['subscriberID' => 1234, 'isActive' => false]);
expect(values()['branch'] === '2' && $households->flat['autoBlock'] === 1, 'omitted branch preserved');
$r = sync(['subscriberID' => 1234, 'branch' => 1, 'note' => 'note']);
expect($r['updated'] === 1 && $households->flat['autoBlock'] === 1, 'custom-only update preserves state');
expect(values()['note'] === 'note', 'second field persisted');

// New field configuration takes effect on the next request without PHP changes.
addField('newProperty');
$r = sync(['subscriberID' => 1234, 'newProperty' => 'new value']);
expect($r['updated'] === 1 && values()['newProperty'] === 'new value', 'dynamic new field');
expect(values()['branch'] === '1' && values()['note'] === 'note', 'patch preserves other fields');

foreach ([3, [], ['x'], 'other'] as $invalid) {
    $before = values(); $state = $households->flat;
    $r = sync(['subscriberID' => 1234, 'isActive' => true, 'branch' => $invalid]);
    expect($r['invalid'] === 1 && $r['updated'] === 0, 'invalid select rejected');
    expect($r['errors'][0]['field'] === 'branch', 'error identifies field');
    expect(values() === $before && $households->flat === $state, 'invalid item has no effects');
}
$r = sync(['subscriberID' => 1234, 'zero' => 0, 'flag' => false]);
expect($r['updated'] === 1 && values()['zero'] === '0' && values()['flag'] === '0', 'zero and false stored');
$r = sync(['subscriberID' => 1234, 'zero' => 0, 'flag' => false]);
expect($r['updated'] === 1 && values()['zero'] === '0', 'zero update idempotent');
$r = sync(['subscriberID' => 1234, 'flag' => true]);
expect(values()['flag'] === '1', 'boolean true stored');
$r = sync(['subscriberID' => 1234, 'flag' => false]);
expect(values()['flag'] === '0', 'boolean false replaces true');
$r = sync(['subscriberID' => 1234, 'branch' => null, 'note' => '']);
expect($r['updated'] === 1 && !isset(values()['branch']) && !isset(values()['note']), 'null and empty clear optional fields');
expect(values()['newProperty'] === 'new value', 'clear preserves omitted fields');

$r = sync(['buildingUUID' => 'house-test', 'flatNumber' => '3', 'branch' => 2]);
expect($r['updated'] === 1 && values()['branch'] === '2', 'address pair lookup');
$before = values();
$r = sync(['subscriberID' => 1234, 'buildingUUID' => 'wrong', 'flatNumber' => '3', 'branch' => 1]);
expect($r['failed'] === 1 && $r['updated'] === 0 && values() === $before, 'mismatched pair cannot silently skip field');
$households->duplicate = true;
$r = sync(['subscriberID' => 1234, 'branch' => 1]);
expect($r['updated'] === 0 && values() === $before, 'ambiguous contract unchanged');
$households->duplicate = false;

$r = sync(['subscriberID' => 1234, 'isActive' => true, 'unknown' => 'ignored', 'houseOnly' => 'ignored']);
expect($r['updated'] === 1 && !isset(values()['unknown']) && !isset(values()['houseOnly']), 'only configured flat fields used');
$r = sync(['subscriberID' => 1234, 'tags' => ['x', 'y'], 'details' => ['answer' => 42], 'freeSelect' => 'new']);
expect($r['updated'] === 1 && json_decode(values()['tags'], true) === ['x', 'y'], 'multiple select serialized');
expect(json_decode(values()['details'], true) === ['answer' => 42], 'JSON serialized');
expect(values()['freeSelect'] === 'new', 'editable select');
foreach ([['tags' => ['z']], ['tags' => 'bad'], ['details' => 'bad'], ['zero' => 'bad'], ['requiredValue' => ''], ['code' => 'bad']] as $item) {
    $before = values();
    $r = sync(['subscriberID' => 1234] + $item);
    expect($r['invalid'] === 1 && $r['updated'] === 0 && values() === $before, 'field format validation');
}
$r = sync(['subscriberID' => 1234, 'code' => 'AB']);
expect($r['updated'] === 1 && values()['code'] === 'AB', 'valid regex');
$failCustomFields = true;
$r = sync(['subscriberID' => 1234, 'isActive' => false, 'branch' => 1]);
expect($r['failed'] === 1 && $r['updated'] === 0 && $households->flat['autoBlock'] === 0, 'missing backend fails before flat changes');
$failCustomFields = false;

// Existing dedicated properties must not be redirected into equally named custom fields.
addField('login');
$r = sync(['subscriberID' => 1234, 'isActive' => true, 'login' => 'test-login', 'password' => 'test-password']);
expect($r['updated'] === 1 && $households->flat['login'] === 'test-login', 'dedicated credentials still update flat');
expect(!isset(values()['login']), 'reserved name not mapped to custom fields');
$before = values();
$r = sync(['subscriberID' => 1234, 'phones' => [['phone' => '+7 999 123-45-67']]]);
expect($r['updated'] === 1 && values() === $before, 'phone-only mode remains supported');
$r = sync(['subscriberID' => 1234, 'unknown' => 'ignored']);
expect($r['invalid'] === 1 && $r['updated'] === 0, 'unknown-only request cannot claim update');
$r = \api\billing\subscriptions::POST(['subscribers' => [
    ['subscriberID' => 1234, 'branch' => 3],
    ['subscriberID' => 1234, 'branch' => 1],
]])[200]['subscriptions'];
expect($r['processed'] === 2 && $r['invalid'] === 1 && $r['updated'] === 1, 'batch isolates invalid item');
expect(values()['branch'] === '1' && $r['errors'][0]['index'] === 0, 'batch value and error index');

// The shared storage backend must preserve zero in both patch and replace modes.
expect($customFields->modifyValues('flat', 99, ['zero' => '0'], 'replace'), 'replace inserts zero');
expect($customFields->modifyValues('flat', 99, ['zero' => '0'], 'replace'), 'replace repeats zero');
expect($customFields->getValues('flat', 99) === ['zero' => '0'], 'replace retains zero');
expect($customFields->modifyValues('flat', 99, ['zero' => ''], 'patch'), 'clear zero');
expect($customFields->getValues('flat', 99) === [], 'zero cleared');
$db->rollBack();
echo "PASS: $checks checks for dynamic custom fields through subscriptions API (PostgreSQL)\n";
