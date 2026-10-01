<?php

// Uses only connection-local temporary tables. No pushes or production backends are loaded.
// RBT_TEST_PG_DSN='pgsql:host=/path/to/socket;dbname=postgres' php tests/address-broadcast.php

require_once __DIR__ . '/../server/utils/PDOExt.php';
require_once __DIR__ . '/../server/backends/backend.php';
require_once __DIR__ . '/../server/backends/households/households.php';
require_once __DIR__ . '/../server/backends/households/internal/internal.php';
require_once __DIR__ . '/../server/api/api.php';
require_once __DIR__ . '/../server/api/inbox/broadcast.php';

function setLastError($error) {
    $GLOBALS['lastError'] = $error;
}

function getLastError() {
    return $GLOBALS['lastError'] ?? '';
}

function loadBackend($name) {
    if ($name !== 'households') {
        throw new RuntimeException("Unexpected backend: $name");
    }
    return $GLOBALS['households'];
}

$checks = 0;
function expect($expected, $actual, $message) {
    global $checks;
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
    $checks++;
}

function insertRow($table, $values) {
    global $db;
    $fields = implode(', ', array_keys($values));
    $params = implode(', ', array_fill(0, count($values), '?'));
    $db->prepare("insert into $table ($fields) values ($params)")->execute(array_values($values));
}

function queuedIds() {
    global $db;
    return array_map('intval', $db->query('select house_subscriber_id from houses_subscribers_messages order by house_subscriber_id')->fetchAll(PDO::FETCH_COLUMN));
}

$dsn = getenv('RBT_TEST_PG_DSN');
if (!$dsn) {
    fwrite(STDERR, "Set RBT_TEST_PG_DSN to a test PostgreSQL database.\n");
    exit(1);
}

$db = new PDOExt($dsn, getenv('RBT_TEST_PG_USER') ?: null, getenv('RBT_TEST_PG_PASSWORD') ?: null);
$db->beginTransaction();
try {
    $db->exec('create temp table broadcast_test_guard (id integer)');
    $db->exec('set local search_path to pg_temp');
    $db->exec(str_replace('CREATE TABLE ', 'CREATE TEMP TABLE ', file_get_contents(__DIR__ . '/../server/data/pgsql/v1_addresses.sql')));
    $db->exec(str_replace('CREATE TABLE ', 'CREATE TEMP TABLE ', file_get_contents(__DIR__ . '/../server/data/pgsql/v78_bulk_messages.sql')));
    $db->exec('create temp table houses_flats (house_flat_id integer primary key, address_house_id integer)');
    $db->exec('create temp table houses_subscribers_mobile (house_subscriber_id integer primary key, id text)');
    $db->exec('create temp table houses_flats_subscribers (house_flat_id integer, house_subscriber_id integer)');

    foreach ([1, 2, 3] as $id) {
        insertRow('addresses_regions', ['address_region_id' => $id, 'region' => "Region $id", 'region_with_type' => "Region $id"]);
    }
    foreach ([10 => 1, 20 => 2, 30 => 1] as $id => $region) {
        insertRow('addresses_areas', ['address_area_id' => $id, 'address_region_id' => $region, 'area' => "Area $id", 'area_with_type' => "Area $id"]);
    }
    foreach ([100 => [1, 0], 101 => [null, 10], 102 => [2, null], 103 => [0, 20], 104 => [1, null]] as $id => [$region, $area]) {
        insertRow('addresses_cities', ['address_city_id' => $id, 'address_region_id' => $region, 'address_area_id' => $area, 'city' => "City $id", 'city_with_type' => "City $id"]);
    }
    foreach ([1000 => [null, 100], 1001 => [10, null], 1002 => [null, 101], 1003 => [20, null], 1004 => [null, 102], 1005 => [null, 103], 1006 => [null, 100]] as $id => [$area, $city]) {
        insertRow('addresses_settlements', ['address_settlement_id' => $id, 'address_area_id' => $area, 'address_city_id' => $city, 'settlement' => "Settlement $id", 'settlement_with_type' => "Settlement $id"]);
    }
    foreach ([10000 => [100, null], 10001 => [null, 1000], 10002 => [null, 1001], 10003 => [101, null], 10004 => [null, 1002], 20000 => [102, null], 20001 => [null, 1003], 20002 => [null, 1004], 20003 => [103, null], 20004 => [null, 1005], 30000 => [100, null]] as $id => [$city, $settlement]) {
        insertRow('addresses_streets', ['address_street_id' => $id, 'address_city_id' => $city, 'address_settlement_id' => $settlement, 'street' => "Street $id", 'street_with_type' => "Street $id"]);
    }
    $houses = [1 => [10000, null], 2 => [10001, null], 3 => [null, 1000], 4 => [10002, null], 5 => [null, 1001], 6 => [10003, null], 7 => [10004, null], 8 => [null, 1002], 9 => [20000, null], 10 => [20001, null], 11 => [null, 1003], 12 => [20002, null], 13 => [null, 1004], 14 => [20003, null], 15 => [20004, null], 16 => [null, 1005], 17 => [30000, null]];
    foreach ($houses as $id => [$street, $settlement]) {
        insertRow('addresses_houses', ['address_house_id' => $id, 'address_street_id' => $street, 'address_settlement_id' => $settlement, 'house' => "$id", 'house_full' => "House $id"]);
        insertRow('houses_flats', ['house_flat_id' => $id, 'address_house_id' => $id]);
        if ($id < 17) {
            insertRow('houses_subscribers_mobile', ['house_subscriber_id' => $id, 'id' => "subscriber-$id"]);
            insertRow('houses_flats_subscribers', ['house_flat_id' => $id, 'house_subscriber_id' => $id]);
        }
    }

    // Subscriber 50 owns multiple flats, including flats in different regions.
    insertRow('houses_subscribers_mobile', ['house_subscriber_id' => 50, 'id' => 'multi-flat']);
    foreach ([1, 2, 9] as $flat) {
        insertRow('houses_flats_subscribers', ['house_flat_id' => $flat, 'house_subscriber_id' => 50]);
    }
    insertRow('houses_flats', ['house_flat_id' => 101, 'address_house_id' => 1]);
    insertRow('houses_flats_subscribers', ['house_flat_id' => 101, 'house_subscriber_id' => 50]);
    // Unlinked subscribers, orphan links and deleted subscribers must not be recipients.
    insertRow('houses_subscribers_mobile', ['house_subscriber_id' => 51, 'id' => 'unlinked']);
    insertRow('houses_subscribers_mobile', ['house_subscriber_id' => 52, 'id' => 'orphan-flat']);
    insertRow('houses_flats_subscribers', ['house_flat_id' => 999, 'house_subscriber_id' => 52]);
    insertRow('houses_flats_subscribers', ['house_flat_id' => 1, 'house_subscriber_id' => 999]);

    $households = new \backends\households\internal(['backends' => ['households' => []]], $db, null, 'admin');
    $scopes = [
        ['houseId', 1, [1, 50]],
        ['streetId', 10000, [1, 50]],
        ['settlementId', 1000, [2, 3, 50]],
        ['settlementId', 1001, [4, 5]],
        ['cityId', 100, [1, 2, 3, 50]],
        ['cityId', 101, [6, 7, 8]],
        ['areaId', 10, [4, 5, 6, 7, 8]],
        ['regionId', 1, [1, 2, 3, 4, 5, 6, 7, 8, 50]],
        ['regionId', '2', [9, 10, 11, 12, 13, 14, 15, 16, 50]],
        ['all', 0, [...range(1, 16), 50]],
        ['all', null, [...range(1, 16), 50]],
        ['all', '0', [...range(1, 16), 50]],
        ['regionId', 3, []], ['areaId', 30, []], ['cityId', 104, []],
        ['settlementId', 1006, []], ['streetId', 30000, []], ['houseId', 17, []],
    ];
    foreach ($scopes as [$by, $query, $expected]) {
        $db->exec('truncate houses_subscribers_messages');
        expect(count($expected), $households->getAddressBroadcastRecipientCount($by, $query), "$by/$query count");
        expect(count($expected), $households->queueAddressBroadcast($by, $query, 'Notice', 'Body'), "$by/$query queue count");
        expect($expected, queuedIds(), "$by/$query recipients");
        expect(0, $households->queueAddressBroadcast($by, $query, 'Notice', 'Body'), "$by/$query pending dedupe");
    }

    $invalid = [[null, null], ['', 0], ['region', 1], ['unknown', 0], [[], 1], ['all', 1], ['all', ''], ['all', false]];
    foreach (['regionId', 'areaId', 'cityId', 'settlementId', 'streetId', 'houseId'] as $by) {
        foreach ([null, 0, '0', -1, '1 OR 1=1', '1.0', 1.0, true, [], '01', 999999, '2147483648'] as $id) {
            $invalid[] = [$by, $id];
        }
    }
    foreach ($invalid as [$by, $query]) {
        expect(false, $households->getAddressBroadcastRecipientCount($by, $query), 'Invalid scope count');
        expect(false, $households->queueAddressBroadcast($by, $query, 'Title', 'Body'), 'Invalid scope queue');
    }
    expect([], queuedIds(), 'Invalid scopes never fall back to all');

    foreach ([['', 'Body', 'inbox'], ['Title', '  ', 'inbox'], [[], 'Body', 'inbox'], ['Title', [], 'inbox'], ['Title', 'Body', 'invalid'], ['Title', 'Body', []]] as [$title, $body, $action]) {
        expect(false, $households->queueAddressBroadcast('all', 0, $title, $body, $action), 'Invalid content');
    }
    expect(2, $households->queueAddressBroadcast('houseId', 1, 'Payment', 'Body', 'money'), 'Money action');
    expect('money', $db->query('select distinct action from houses_subscribers_messages')->fetchColumn(), 'Action preserved');
    expect(15, $households->queueAddressBroadcast('all', 0, 'Payment', 'Body', 'money'), 'Overlapping scopes do not duplicate pending messages');
    $db->exec('truncate houses_subscribers_messages');

    $api = \api\inbox\broadcast::class;
    expect(['GET' => '#same(addresses,house,PUT)', 'POST' => '#same(addresses,house,PUT)'], $api::index(), 'Existing send permission required for both endpoints');
    $preview = $api::GET(['by' => 'regionId', 'query' => 1]);
    expect(9, $preview[200]['audience']['count'], 'API preview');
    expect(['cache' => 0], end($preview), 'Preview is not cached');
    expect([], queuedIds(), 'Preview has no side effects');
    // Membership may change after preview. POST resolves current recipients server-side.
    $db->exec('delete from houses_flats_subscribers where house_subscriber_id = 1');
    $queued = $api::POST(['by' => 'regionId', 'query' => 1, 'title' => 'Update', 'body' => 'Details']);
    expect(8, $queued[200]['queued']['count'], 'POST refreshes audience');
    expect([2, 3, 4, 5, 6, 7, 8, 50], queuedIds(), 'API queues correct recipients');
    expect(0, $api::POST(['by' => 'regionId', 'query' => 1, 'title' => 'Update', 'body' => 'Details'])[200]['queued']['count'], 'API duplicate reports zero');
    expect(0, $api::GET(['by' => 'regionId', 'query' => 3])[200]['audience']['count'], 'Empty audience is successful');
    expect('invalidParams', $api::GET(['by' => 'houseId'])[400]['error'], 'Missing ID rejected');
    expect('invalidParams', $api::POST(['by' => 'all'])[400]['error'], 'Missing message rejected');
    expect(1, $api::POST(['by' => 'houseId', 'query' => 1, 'title' => '0', 'body' => '0'])[200]['queued']['count'], 'Nonempty zero strings accepted');
    expect('inbox', $db->query("select action from houses_subscribers_messages where title = '0'")->fetchColumn(), 'Default action preserved');

    // A queue write error must not produce a successful queued response.
    $db->exec('savepoint queue_failure');
    $db->exec('alter table houses_subscribers_messages add constraint reject_test_title check (title <> \'Rejected\')');
    ob_start();
    $previousErrorLog = ini_set('error_log', '/dev/null');
    try {
        expect(false, $households->queueAddressBroadcast('all', 0, 'Rejected', 'Body'), 'Queue error propagated');
    } finally {
        ini_set('error_log', $previousErrorLog);
        ob_end_clean();
        $db->exec('rollback to savepoint queue_failure');
    }

    // A larger audience must be queued completely by the same bulk path.
    $db->exec('truncate houses_subscribers_messages');
    $db->exec("insert into houses_subscribers_mobile select id, 'bulk-' || id from generate_series(1000, 10999) id");
    $db->exec('insert into houses_flats_subscribers select 1, id from generate_series(1000, 10999) id');
    expect(10001, $households->getAddressBroadcastRecipientCount('houseId', 1), 'Large audience count');
    expect(10001, $households->queueAddressBroadcast('houseId', 1, 'Bulk', 'Body'), 'Large audience queued');
    expect(10001, (int)$db->query('select count(*) from houses_subscribers_messages')->fetchColumn(), 'All bulk rows persisted');
    expect(0, $households->queueAddressBroadcast('houseId', 1, 'Bulk', 'Body'), 'Large audience pending dedupe');

    echo "OK: $checks address broadcast checks\n";
} finally {
    $db->rollBack();
}
