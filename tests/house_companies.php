<?php
// Run against a test database: RBT_TEST_DSN='pgsql:host=...;dbname=...' php tests/house_companies.php
// All fixtures live in a randomly named schema, removed on exit.
$dsn = getenv('RBT_TEST_DSN');
if (!$dsn) {
    fwrite(STDERR, "Set RBT_TEST_DSN to a PostgreSQL test database\n");
    exit(2);
}
require_once __DIR__ . '/../server/utils/PDOExt.php';
require_once __DIR__ . '/../server/utils/functions.php';
require_once __DIR__ . '/../server/backends/backend.php';
require_once __DIR__ . '/../server/backends/addresses/addresses.php';
require_once __DIR__ . '/../server/backends/addresses/internal/internal.php';
require_once __DIR__ . '/../server/backends/households/households.php';
require_once __DIR__ . '/../server/backends/households/internal/internal.php';
require_once __DIR__ . '/../server/backends/queue/queue.php';
require_once __DIR__ . '/../server/backends/queue/internal/internal.php';
require_once __DIR__ . '/../server/backends/billing/billing.php';
require_once __DIR__ . '/../server/api/api.php';
require_once __DIR__ . '/../server/api/addresses/house.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function setLastError($error) { $GLOBALS['lastError'] = $error; }
function getLastError() { return $GLOBALS['lastError'] ?? ''; }
function i18n($value) { return $value; }
// Fixtures are plain text; HTML sanitization is outside this test's scope.
function htmlPurifier($value) { return $value; }
function loadBackend($name) { return $GLOBALS['testBackends'][$name] ?? false; }

$db = new PDOExt($dsn, getenv('RBT_TEST_USER') ?: null, getenv('RBT_TEST_PASSWORD') ?: null);
$schema = 'test_house_companies_' . bin2hex(random_bytes(6));
$db->exec("CREATE SCHEMA $schema; SET search_path TO $schema, public");
register_shutdown_function(function () use ($db, $schema) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec("DROP SCHEMA $schema CASCADE");
});
$db->exec("CREATE EXTENSION IF NOT EXISTS fuzzystrmatch WITH SCHEMA $schema");
$migration = file_get_contents(__DIR__ . '/../server/data/pgsql/v98_house_companies.sql');
$manifest = json_decode(file_get_contents(__DIR__ . '/../server/data/install.json'), true);
check(in_array('v98_house_companies.sql', $manifest['98']), 'Migration not registered');

$db->exec(<<<'SQL'
CREATE TABLE companies (company_id integer PRIMARY KEY);
INSERT INTO companies VALUES (1), (2), (3);
CREATE TABLE addresses_houses (
    address_house_id serial PRIMARY KEY,
    address_settlement_id integer, address_street_id integer,
    house_uuid text, house_type text, house_type_full text,
    house_full text NOT NULL, house text NOT NULL,
    company_id integer DEFAULT 0, company integer
);
INSERT INTO addresses_houses (address_house_id, house_full, house, company_id, company) VALUES
    (11, 'House 11', '11', 1, 2), (12, 'House 12', '12', 0, NULL),
    (13, 'House 13', '13', 2, 2), (14, 'House 14', '14', NULL, 3),
    (15, 'House 15', '15', 999, NULL);
SQL);

try {
    $db->exec($migration);
    throw new RuntimeException('Migration accepted a dangling company reference');
} catch (PDOException $expected) {
    check($expected->getCode() === '23503', 'Unexpected migration error');
}
check($db->query("select company_id from addresses_houses where address_house_id = 15")->fetchColumn() == 999, 'Failed migration lost the old reference');
check($db->query("select to_regclass('addresses_houses_companies')")->fetchColumn() === null, 'Failed migration was not atomic');
$db->exec('DELETE FROM addresses_houses WHERE address_house_id = 15');
$db->exec($migration);
check($db->query('select count(*) from addresses_houses_companies')->fetchColumn() == 4, 'Migration lost or duplicated links');
check($db->query("select count(*) from pg_attribute where attrelid = 'addresses_houses'::regclass and attname in ('company', 'company_id') and not attisdropped")->fetchColumn() == 0, 'Legacy storage retained');
$db->exec($migration);
check($db->query('select count(*) from addresses_houses_companies')->fetchColumn() == 4, 'Migration is not repeatable');

$db->exec(<<<'SQL'
CREATE TABLE tasks_changes (object_type text, object_id integer, UNIQUE (object_type, object_id));
CREATE TABLE houses_domophones (
    house_domophone_id integer PRIMARY KEY, enabled integer, model text, server text, url text,
    credentials text, dtmf text, first_time integer, nat integer, locks_are_open integer,
    comments text, name text, ip text, sub_id text, display text, video text,
    monitoring integer, ext text, concierge integer, sos integer, tree text
);
INSERT INTO houses_domophones (house_domophone_id, enabled) VALUES (101, 1), (102, 1);
CREATE TABLE houses_entrances (house_entrance_id integer, house_domophone_id integer);
CREATE TABLE houses_houses_entrances (address_house_id integer, house_entrance_id integer);
INSERT INTO houses_entrances VALUES (201, 101), (202, 102);
CREATE TABLE houses_entrances_flats (house_entrance_id integer, house_flat_id integer);
CREATE TABLE houses_flats_subscribers (house_flat_id integer, house_subscriber_id integer);
CREATE TABLE houses_rfids (house_rfid_id integer, rfid text, access_type integer, access_to integer, last_seen integer, comments text);
INSERT INTO houses_rfids (house_rfid_id, rfid, access_type, access_to) VALUES
    (1, 'KEY1', 5, 1), (2, 'KEY2', 5, 2), (3, 'KEY3', 5, 3);
SQL);
$config = ['backends' => ['addresses' => [], 'households' => [], 'queue' => []], 'db' => []];
$params = [];
$addresses = new \backends\addresses\internal($config, $db, false);
$households = new \backends\households\internal($config, $db, false);
$queue = new \backends\queue\internal($config, $db, false);
$testBackends = ['addresses' => $addresses, 'households' => $households, 'queue' => $queue];

check($addresses->getHouse(11)['companyIds'] === [1, 2], 'Migrated house read failed');
check($addresses->getHouse(12)['companyIds'] === [], 'Unassigned house read failed');
check($addresses->getHouse(11)['companyId'] === 1, 'Legacy projection missing');
$houseId = $addresses->addHouse(1, 0, 'uuid', '', '', 'New house', '1', ['2', 1, 2]);
check($houseId !== false && $addresses->getHouse($houseId)['companyIds'] === [1, 2], 'Multiple-company create failed');
check($addresses->getHouses(1)[0]['companyIds'] === [1, 2], 'House listing omitted companies');
check($addresses->searchHouse('New house')[0]['companyIds'] === [1, 2], 'House search omitted companies');
$db->exec("INSERT INTO houses_houses_entrances VALUES ($houseId, 201), (14, 202)");
check(array_column($households->getDomophones('company', 2), 'domophoneId') === [101], 'Company device lookup failed');
check(array_column($households->getKeys('domophoneId', 101), 'rfId') !== [], 'Company RFID lookup empty');
$keys = array_column($households->getKeys('domophoneId', 101), 'rfId');
sort($keys);
check($keys === ['KEY1', 'KEY2'], 'RFIDs do not include exactly the linked companies');

$values = [$houseId, 1, 0, 'uuid', '', '', 'Updated house', '1'];
check($addresses->modifyHouse(...$values) !== false, 'Omitted links update failed');
check($addresses->getHouse($houseId)['companyIds'] === [1, 2], 'Omitted links were cleared');
check($addresses->modifyHouse(...[...$values, 1]) !== false, 'Legacy unchanged projection rejected');
check($addresses->getHouse($houseId)['companyIds'] === [1, 2], 'Legacy round trip lost extra links');
check($addresses->modifyHouse(...[...$values, 3]) === false, 'Legacy scalar silently replaced multiple companies');
check($addresses->modifyHouse(...[...$values, [2, 3]]) !== false, 'Replace companies failed');
check($addresses->getHouse($houseId)['companyIds'] === [2, 3], 'Replacement/cache invalidation failed');
check($db->query("select count(*) from tasks_changes where object_type = 'domophone' and object_id = 101")->fetchColumn() == 1, 'Link change did not enqueue house devices');
$keys = array_column($households->getKeys('domophoneId', 101), 'rfId');
sort($keys);
check($keys === ['KEY2', 'KEY3'], 'RFID removal/addition did not follow new links');
check($addresses->modifyHouse(...[...$values, []]) !== false, 'Clear companies failed');
check($addresses->getHouse($houseId)['companyIds'] === [], 'Empty selection retained links');
$db->exec($migration);
check($addresses->getHouse($houseId)['companyIds'] === [], 'Rerunning migration resurrected cleared links');

foreach ([[0], [-1], ['bad'], [true], [1.5], ['key' => 1], [2147483648]] as $invalid) {
    check($addresses->modifyHouse(...[...$values, $invalid]) === false, 'Invalid company list accepted');
}
check($addresses->modifyHouse(...[...$values, [1, 999]]) === false, 'Missing organization accepted');
check($addresses->getHouse($houseId)['companyIds'] === [], 'Failed update left partial links');
$before = $db->query('select count(*) from addresses_houses')->fetchColumn();
check($addresses->addHouse(1, 0, 'bad', '', '', 'Bad house', '2', [1, 999]) === false, 'Invalid create succeeded');
check($db->query('select count(*) from addresses_houses')->fetchColumn() == $before, 'Invalid create left an orphan house');

$db->beginTransaction();
check($addresses->modifyHouse(...[...$values, [999]]) === false, 'Nested invalid update succeeded');
check($db->inTransaction() && $db->query('select 1')->fetchColumn() == 1, 'Nested failure aborted caller transaction');
check($addresses->modifyHouse(...[...$values, [1, 2]]) !== false, 'Nested valid update failed');
$db->rollBack();
$addresses = new \backends\addresses\internal($config, $db, false);
$testBackends['addresses'] = $addresses;
check($addresses->getHouse($houseId)['companyIds'] === [], 'Outer rollback did not roll back links');

$payload = ['_id' => $houseId, 'settlementId' => 1, 'streetId' => 0, 'houseUuid' => 'uuid', 'houseType' => '', 'houseTypeFull' => '', 'houseFull' => 'API house', 'house' => '1'];
$created = \api\addresses\house::POST($payload + ['companyIds' => [1, 2]]);
check(isset($created[200]['houseId']) && $addresses->getHouse($created[200]['houseId'])['companyIds'] === [1, 2], 'API create lost organizations');
\api\addresses\house::PUT($payload + ['companyIds' => [1, 2], 'companyId' => 3]);
check($addresses->getHouse($houseId)['companyIds'] === [1, 2], 'API array did not take precedence');
\api\addresses\house::PUT($payload);
check($addresses->getHouse($houseId)['companyIds'] === [1, 2], 'API omission cleared links');
foreach ([null, 1, '1', false] as $invalid) {
    \api\addresses\house::PUT($payload + ['companyIds' => $invalid]);
    check($addresses->getHouse($houseId)['companyIds'] === [1, 2], 'Malformed API value changed links');
}
\api\addresses\house::PUT($payload + ['companyIds' => []]);
check($addresses->getHouse($houseId)['companyIds'] === [], 'API clear failed');
\api\addresses\house::PUT($payload + ['companyId' => 2]);
check($addresses->getHouse($houseId)['companyIds'] === [2], 'Legacy API scalar failed');

// Exercise the real billing importer; only the unrelated address hierarchy is stubbed.
$testBackends['addresses'] = new class($addresses) {
    public function __construct(private $addresses) {}
    public function getRegions() { return [['regionId' => 1, 'regionUuid' => 'region', 'region' => 'Region']]; }
    public function getCities(...$args) { return [['cityId' => 1, 'cityUuid' => 'city', 'city' => 'City']]; }
    public function getStreets(...$args) { return [['streetId' => 7, 'streetUuid' => 'street', 'street' => 'Street']]; }
    public function __call($method, $args) { return $this->addresses->$method(...$args); }
};
$testBackends['customFields'] = new stdClass();
$billing = new class extends \backends\billing\billing {
    public function __construct() {}
    public function getSubscriberAccountInfo($login, $password) { return false; }
    public function getSubscriberAdditionalServices($login, $password, $agrmid) { return false; }
};
$import = ['regionUuid' => 'region', 'region' => 'Region', 'cityUuid' => 'city', 'city' => 'City', 'streetUuid' => 'street', 'street' => 'Street', 'houseUuid' => 'billing-house', 'house' => '42'];
$result = $billing->importAddressHierarchy([$import + ['companyIds' => [2, 1]]]);
check($result['failed'] === 0 && $result['created']['houses'] === 1, 'Billing create failed: ' . json_encode($result));
$importedHouse = $addresses->getHouses(false, 7)[0];
$importedId = $importedHouse['houseId'];
check($importedHouse['companyIds'] === [1, 2], 'Billing create lost companies');
$result = $billing->importAddressHierarchy([$import]);
check($result['failed'] === 0 && $addresses->getHouse($importedId)['companyIds'] === [1, 2], 'Billing omission cleared companies');
$result = $billing->importAddressHierarchy([$import + ['companyId' => 1]]);
check($result['failed'] === 0 && $addresses->getHouse($importedId)['companyIds'] === [1, 2], 'Legacy billing dropped extra companies');
$result = $billing->importAddressHierarchy([$import + ['companyIds' => [2, 3]]]);
check($result['failed'] === 0 && $addresses->getHouse($importedId)['companyIds'] === [2, 3], 'Billing replacement failed');
$result = $billing->importAddressHierarchy([$import + ['companyIds' => []]]);
check($result['failed'] === 0 && $addresses->getHouse($importedId)['companyIds'] === [], 'Billing clear failed');
$result = $billing->importAddressHierarchy([$import + ['companyIds' => '2']]);
check($result['invalid'] === 1, 'Billing accepted a malformed array');
$testBackends['addresses'] = $addresses;

$db->exec('DELETE FROM companies WHERE company_id = 2');
check($db->query('select count(*) from addresses_houses_companies where company_id = 2')->fetchColumn() == 0, 'Company deletion did not clean links');
$db->exec('DELETE FROM addresses_houses WHERE address_house_id = 14');
check($db->query('select count(*) from addresses_houses_companies where address_house_id = 14')->fetchColumn() == 0, 'House deletion did not clean links');
echo "Migration, API, billing, transactions, validation, device lookup and RFID tests passed\n";
