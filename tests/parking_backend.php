<?php
// Run against a test database: RBT_TEST_DSN='pgsql:host=...;dbname=...' php tests/parking_backend.php
// All fixtures live in a randomly named schema, removed on exit.
$dsn = getenv('RBT_TEST_DSN');
if (!$dsn) {
    fwrite(STDERR, "Set RBT_TEST_DSN to a PostgreSQL test database\n");
    exit(2);
}
require_once __DIR__ . '/../server/utils/PDOExt.php';
require_once __DIR__ . '/../server/utils/functions.php';
require_once __DIR__ . '/../server/backends/backend.php';
require_once __DIR__ . '/../server/backends/parking/parking.php';
require_once __DIR__ . '/../server/backends/parking/internal/internal.php';
require_once __DIR__ . '/../server/backends/cameras/cameras.php';
require_once __DIR__ . '/../server/backends/cameras/internal/internal.php';
require_once __DIR__ . '/../server/api/api.php';
require_once __DIR__ . '/../server/api/cameras/parking.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function setLastError($error) { $GLOBALS['lastError'] = $error; }
function getLastError() { return $GLOBALS['lastError'] ?? ''; }
function i18n($value) { return $value; }
function loadBackend($name) { return $GLOBALS['testBackends'][$name] ?? false; }

$db = new PDOExt($dsn, getenv('RBT_TEST_USER') ?: null, getenv('RBT_TEST_PASSWORD') ?: null);
$schema = 'test_parking_' . bin2hex(random_bytes(6));
$db->exec("CREATE SCHEMA $schema; SET search_path TO $schema, public");
register_shutdown_function(function () use ($db, $schema) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec("DROP SCHEMA $schema CASCADE");
});

$manifest = json_decode(file_get_contents(__DIR__ . '/../server/data/install.json'), true);
check(in_array('v101_parking.sql', $manifest['101']), 'Migration not registered');
$tables = json_decode(file_get_contents(__DIR__ . '/../server/data/tables.json'), true);
foreach (['parking_configs', 'parking_areas', 'parking_config_revisions'] as $table) {
    check(in_array($table, $tables), "$table is not moved by schema.php");
}

$db->exec('CREATE TABLE cameras (camera_id serial PRIMARY KEY, model text)');
$db->exec("INSERT INTO cameras (model) VALUES ('fake.json'), ('fake.json'), ('fake.json')");
$migration = file_get_contents(__DIR__ . '/../server/data/pgsql/v101_parking.sql');
$db->exec($migration);
$db->exec($migration);

$config = ['backends' => ['parking' => ['backend' => 'internal'], 'cameras' => ['backend' => 'internal']]];
$parking = new backends\parking\internal($config, $db, null, 'admin');
$cameras = new backends\cameras\internal($config, $db, null, 'admin');
$GLOBALS['testBackends'] = ['parking' => $parking];

$fixtures = json_decode(file_get_contents(__DIR__ . '/parking_fixtures.json'), true)['cases'];
$fixture = function ($name) use ($fixtures) {
    foreach ($fixtures as $case) {
        if (str_starts_with($case['name'], $name)) {
            return $case['config'];
        }
    }
    throw new RuntimeException("no fixture $name");
};
$count = fn ($query) => (int)$db->query($query)->fetchColumn();
$api = fn ($method, $id, $body = []) => api\cameras\parking::$method(['_id' => (string)$id] + $body);
$code = fn ($answer) => array_key_first($answer);

$tests = [];

$tests['camera without configuration'] = function () use ($api, $code) {
    check($code($api('GET', 1)) === 204, 'GET should answer 204');
};

$tests['first save creates configuration, areas and revision'] = function () use ($api, $code, $count, $fixture, $db) {
    $answer = $api('PUT', 1, ['revision' => 0] + $fixture('several areas'));
    check($code($answer) === 200, 'PUT failed: ' . json_encode($answer));
    $saved = $answer[200]['parking'];
    check($saved['revision'] === 1 && $saved['ready'] === true, 'revision or ready');
    check(array_column($saved['areas'], 'capacity') === [10, 7, 6], 'capacity');
    check(array_column($saved['areas'], 'name') === ['A', 'B', 'C'], 'order');
    check(count($saved['homography']) === 9, 'homography');
    check($count('select count(*) from parking_areas') === 3, 'areas');
    $snapshot = json_decode($db->query('select snapshot from parking_config_revisions where revision = 1')->fetchColumn(), true);
    check($snapshot['snapshotVersion'] === 1 && $snapshot['layoutVersion'] === 1 && $snapshot['cameraId'] === 1, 'snapshot header');
    check(array_column($snapshot['areas'], 'parkingAreaId') === array_column($saved['areas'], 'parkingAreaId'), 'snapshot ids');
    check(count($snapshot['areas'][0]['metricPoints']) === 4 && count($snapshot['calibration']['homography']) === 9, 'snapshot geometry');
    check($count('select ready from parking_config_revisions where revision = 1') === 1, 'revision ready');
};

$tests['saved configuration is read back unchanged'] = function () use ($api, $fixture) {
    $read = $api('GET', 1)[200]['parking'];
    $input = $fixture('several areas');
    foreach ($input['areas'] as $i => $area) {
        $stored = $read['areas'][$i];
        check($stored['spaceWidth'] == $area['spaceWidth'] && $stored['spaceLength'] == $area['spaceLength'], 'parameters');
        // the areas of this camera are canonicalized: compare the vertices as a set
        $a = array_map(fn ($p) => round($p['u'], 12) . ':' . round($p['v'], 12), $stored['imagePoints']);
        $b = array_map(fn ($p) => round($p['u'], 12) . ':' . round($p['v'], 12), $area['imagePoints']);
        sort($a);
        sort($b);
        check($a === $b, 'vertices');
    }
    check(count($read['calibration']['points']) === 4 && $read['calibration']['checks'][0]['length'] == 2.5, 'calibration');
    check($read['ready'] === true && $read['problems'] === [], 'problems');
};

$tests['repeated save updates, adds and deletes areas'] = function () use ($api, $code, $count, $fixture) {
    $read = $api('GET', 1)[200]['parking'];
    $areas = $read['areas'];
    $ids = array_column($areas, 'parkingAreaId');
    $areas[0]['name'] = 'A1';
    unset($areas[2]);
    $areas = array_values($areas);
    $body = ['revision' => 1, 'enabled' => 1, 'frameWidth' => $read['frameWidth'], 'frameHeight' => $read['frameHeight'], 'calibration' => $read['calibration'], 'areas' => array_slice($areas, 0, 1)];
    $answer = $api('PUT', 1, $body);
    check($code($answer) === 200, 'PUT failed: ' . json_encode($answer));
    $saved = $answer[200]['parking'];
    check($saved['revision'] === 2 && count($saved['areas']) === 1 && $saved['areas'][0]['parkingAreaId'] === $ids[0] && $saved['areas'][0]['name'] === 'A1', 'update and delete');
    $body['revision'] = 2;
    $body['areas'][] = ['name' => 'D'] + $fixture('trucks')['areas'][0];
    $saved = $api('PUT', 1, $body)[200]['parking'];
    check($saved['revision'] === 3 && count($saved['areas']) === 2 && $saved['areas'][1]['parkingAreaId'] > max($ids), 'insert');
    check($count('select count(*) from parking_config_revisions') === 3, 'revisions are kept');
    check($count('select count(*) from parking_areas') === 2, 'areas');
};

$state = fn () => json_encode([
    $db->query('select revision, updated_at, homography::text from parking_configs order by camera_id')->fetchAll(PDO::FETCH_ASSOC),
    $db->query('select * from parking_areas order by parking_area_id')->fetchAll(PDO::FETCH_ASSOC),
    $db->query('select count(*) from parking_config_revisions')->fetchColumn(),
]);

$tests['stale revision is rejected'] = function () use ($api, $code, $state) {
    $before = $state();
    $body = $api('GET', 1)[200]['parking'];
    $body['revision'] = 2;
    $answer = $api('PUT', 1, $body);
    check($code($answer) === 400 && $answer[400]['error'] === 'parkingRevisionConflict', json_encode($answer));
    check($state() === $before, 'nothing changes');
};

$tests['new configuration must start from revision 0'] = function () use ($api, $code, $fixture, $count) {
    $answer = $api('PUT', 2, ['revision' => 1] + $fixture('cars'));
    check($code($answer) === 400 && $answer[400]['error'] === 'parkingRevisionConflict', json_encode($answer));
    check($count('select count(*) from parking_configs where camera_id = 2') === 0, 'nothing created');
};

$tests['invalid geometry is rejected by the server'] = function () use ($api, $code, $state, $fixture) {
    $before = $state();
    $body = ['revision' => 3] + $fixture('self-intersecting');
    $answer = $api('PUT', 1, $body);
    check($code($answer) === 400 && $answer[400]['error'] === 'parkingInvalidConfig' && getLastError() === 'areaSelfIntersecting', json_encode($answer));
    $answer = $api('PUT', 1, ['revision' => 3, 'frameWidth' => '1920'] + $fixture('cars'));
    check($code($answer) === 400 && getLastError() === 'invalidFrame', 'string frame');
    $answer = $api('PUT', 1, $fixture('cars'));
    check($code($answer) === 400 && getLastError() === 'invalidRevision', 'missing revision');
    check($state() === $before, 'nothing changes');
};

$tests['areas of another camera are not taken over'] = function () use ($api, $code, $state, $fixture) {
    $other = $api('PUT', 2, ['revision' => 0] + $fixture('cars'))[200]['parking'];
    $before = $state();
    $body = $api('GET', 1)[200]['parking'];
    $body['areas'][0]['parkingAreaId'] = $other['areas'][0]['parkingAreaId'];
    $answer = $api('PUT', 1, $body);
    check($code($answer) === 400 && $answer[400]['error'] === 'parkingInvalidConfig' && getLastError() === 'invalidArea', json_encode($answer));
    check($state() === $before, 'nothing changes');
};

$tests['unknown camera'] = function () use ($api, $code, $fixture) {
    $answer = $api('PUT', 99, ['revision' => 0] + $fixture('cars'));
    check($code($answer) === 404, json_encode($answer));
};

$tests['failure in the middle of a save is rolled back'] = function () use ($api, $code, $state, $db) {
    $db->exec("create function parking_test_fail() returns trigger language plpgsql as \$\$ begin raise exception 'forced failure'; end; \$\$");
    $db->exec('create trigger parking_test_fail before insert on parking_config_revisions for each row execute function parking_test_fail()');
    try {
        $before = $state();
        $body = $api('GET', 1)[200]['parking'];
        $body['areas'][0]['name'] = 'changed';
        $body['areas'][] = $body['areas'][1];
        unset($body['areas'][1]);
        $body['areas'] = array_values($body['areas']);
        $answer = $api('PUT', 1, $body);
        check($code($answer) === 400, json_encode($answer));
        check($state() === $before, 'partial save');
        // an outer transaction stays usable: the backend rolls back to its savepoint only
        $db->beginTransaction();
        $answer = $api('PUT', 1, $body);
        check($code($answer) === 400, 'outer transaction');
        check($db->query('select 1')->fetchColumn() == 1, 'transaction is aborted');
        $db->rollBack();
    } finally {
        $db->exec('drop trigger parking_test_fail on parking_config_revisions');
    }
};

$tests['configuration without calibration is saved as not ready'] = function () use ($api, $code, $fixture, $db) {
    $answer = $api('PUT', 3, ['revision' => 0] + $fixture('no calibration'));
    check($code($answer) === 200, json_encode($answer));
    $saved = $answer[200]['parking'];
    check($saved['ready'] === false && $saved['homography'] === null && $saved['areas'][0]['capacity'] === null, 'not ready');
    check(array_column($saved['problems'], 'code') === ['noCalibration'], 'problems');
    check($db->query('select ready from parking_config_revisions where camera_id = 3')->fetchColumn() == 0, 'revision');
};

$tests['database constraints'] = function () use ($db) {
    $configId = (int)$db->query('select parking_config_id from parking_configs where camera_id = 1')->fetchColumn();
    $points = '[{"u":0,"v":0},{"u":1,"v":0},{"u":1,"v":1},{"u":0,"v":1}]';
    $bad = [
        "insert into parking_areas (parking_config_id, image_points) values ($configId, '[1,2,3]')",
        "insert into parking_areas (parking_config_id, image_points, base_edge) values ($configId, '$points', 4)",
        "insert into parking_areas (parking_config_id, image_points, space_width) values ($configId, '$points', 0)",
        "insert into parking_areas (parking_config_id, image_points, space_angle) values ($configId, '$points', 91)",
        "insert into parking_areas (parking_config_id, image_points) values (0, '$points')",
        "update parking_configs set homography = '{1,2,3,4,5,6,7,8}' where parking_config_id = $configId",
        "update parking_configs set homography = null where parking_config_id = $configId",
        "insert into parking_configs (camera_id, frame_width, frame_height) values (1, 1920, 1080)",
        "insert into parking_configs (camera_id, frame_width, frame_height) values (99, 1920, 1080)",
    ];
    $db->beginTransaction();
    try {
        foreach ($bad as $query) {
            $db->exec('savepoint c');
            try {
                $db->exec($query);
                throw new RuntimeException("accepted: $query");
            } catch (PDOException $expected) {
                $db->exec('rollback to savepoint c');
            }
        }
    } finally {
        $db->rollBack();
    }
};

$tests['deleting a camera removes its parking data'] = function () use ($cameras, $count) {
    check($cameras->deleteCamera(1) !== false, 'deleteCamera');
    check($count('select count(*) from parking_configs where camera_id = 1') === 0, 'config');
    check($count('select count(*) from parking_config_revisions where camera_id = 1') === 0, 'revisions');
    check($count('select count(*) from parking_areas a join parking_configs c using (parking_config_id) where c.camera_id = 1') === 0, 'areas');
    check($count('select count(*) from parking_areas') === 2, 'areas of other cameras are kept');
    check($count('select count(*) from parking_configs') === 2, 'other configurations are kept');
};

$tests['DELETE removes a configuration'] = function () use ($api, $code, $count) {
    check($code($api('DELETE', 2)) === 200, 'DELETE');
    check($code($api('GET', 2)) === 204, 'GET after DELETE');
    check($count('select count(*) from parking_config_revisions where camera_id = 2') === 0, 'revisions');
    check($code($api('DELETE', 2)) === 200, 'repeated DELETE');
};

$tests['parking backend is not configured'] = function () use ($api, $code) {
    $saved = $GLOBALS['testBackends'];
    $GLOBALS['testBackends'] = [];
    try {
        check($code($api('GET', 3)) === 404, 'GET');
        check(api\cameras\parking::index() === false, 'index');
    } finally {
        $GLOBALS['testBackends'] = $saved;
    }
    check(api\cameras\parking::index()['PUT'] === '#same(addresses,house,PUT)', 'rights');
};

$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "ok - $name\n";
    } catch (Throwable $e) {
        $failed++;
        echo "not ok - $name: {$e->getMessage()}\n";
    }
}

if ($failed) {
    echo "$failed failed\n";
    exit(1);
}

echo "all passed\n";
