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
require_once __DIR__ . '/../server/api/cameras/parkingResult.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function setLastError($error) { $GLOBALS['lastError'] = $error; }
function getLastError() { return $GLOBALS['lastError'] ?? ''; }
function i18n($value) { return $value; }
function loadBackend($name) { return $GLOBALS['testBackends'][$name] ?? false; }
// internal API scripts answer with response() and exit
class testResponse extends Exception {
    public function __construct(public $status, public $data) { parent::__construct("response $status"); }
}
function response($code = 204, $data = false) { throw new testResponse($code, $data); }
function internal($name, $param = null, $postdata = null) {
    try {
        require __DIR__ . "/../server/internal/parking/$name.php";
    } catch (testResponse $r) {
        return [$r->status, $r->data];
    }
    throw new RuntimeException("$name answered nothing");
}

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
foreach (['parking_configs', 'parking_areas', 'parking_config_revisions', 'parking_results', 'parking_statuses'] as $table) {
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
// files backend in memory: frames of the occupancy results
class fakeFiles {
    public $files = [];
    public $failing = false;
    private $next = 1;
    public function contentsToStream($contents) {
        $fd = fopen('php://temp', 'w+');
        fwrite($fd, $contents);
        fseek($fd, 0);
        return $fd;
    }
    public function addFile($name, $stream, $metadata = []) {
        if ($this->failing) {
            throw new RuntimeException('storage is down');
        }
        $uuid = sprintf('%024x', $this->next++);
        $this->files[$uuid] = ['name' => $name, 'contents' => stream_get_contents($stream), 'metadata' => $metadata];
        return $uuid;
    }
    public function getFileStream($uuid) {
        if (!isset($this->files[$uuid])) {
            throw new RuntimeException('no file');
        }
        return $this->contentsToStream($this->files[$uuid]['contents']);
    }
    public function deleteFile($uuid) {
        unset($this->files[$uuid]);
        return true;
    }
}
$files = new fakeFiles();
// only its presence matters: the files backend writes to it
$GLOBALS['testBackends'] = ['parking' => $parking, 'files' => $files, 'tmpfs' => new stdClass()];

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
    $answer = $api('GET', 1);
    check($code($answer) === 200 && $answer[200]['parking'] === null, 'GET should answer an empty configuration');
    check($answer[200]['settings'] === ['interval' => 60, 'minInterval' => 10], 'default settings');
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
    check($saved['detectionImageSize'] === 320 && $saved['confidenceThreshold'] === 0.5 && $saved['processingInterval'] === null, 'defaults');
    check($snapshot['detectionImageSize'] === 320 && $snapshot['confidenceThreshold'] === 0.5 && array_key_exists('processingInterval', $snapshot) && $snapshot['processingInterval'] === null, 'snapshot defaults');
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

$tests['disabled configuration is saved as ready'] = function () use ($api, $code, $fixture, $db) {
    $answer = $api('PUT', 3, ['revision' => 1] + $fixture('disabled configuration'));
    check($code($answer) === 200, json_encode($answer));
    $saved = $answer[200]['parking'];
    check($saved['enabled'] === 0 && $saved['ready'] === true && $saved['problems'] === [], 'ready while disabled');
    check($db->query('select ready from parking_config_revisions where camera_id = 3 and revision = 2')->fetchColumn() == 1, 'revision');
};

$tests['detection and interval parameters are validated, saved and kept in the revision'] = function () use ($api, $code, $fixture, $db, $parking) {
    $base = $fixture('cars');
    $put = fn ($revision, $values) => $api('PUT', 3, ['revision' => $revision] + $values + $base);
    $read = fn () => $api('GET', 3)[200]['parking'];
    $revision = $read()['revision'];
    foreach ([
        ['detectionImageSize' => 159], ['detectionImageSize' => 1025], ['detectionImageSize' => 320.5], ['detectionImageSize' => '320'],
        ['confidenceThreshold' => -0.01], ['confidenceThreshold' => 1.01], ['confidenceThreshold' => 'x'],
        ['processingInterval' => 9], ['processingInterval' => 0], ['processingInterval' => 86401], ['processingInterval' => 10.5],
    ] as $values) {
        $answer = $put($revision, $values);
        check($code($answer) === 400 && $answer[400]['error'] === 'parkingInvalidConfig', 'rejected ' . json_encode($values));
    }
    check($read()['revision'] === $revision, 'nothing saved');
    $answer = $put($revision, ['detectionImageSize' => 640, 'confidenceThreshold' => 0.35, 'processingInterval' => 10]);
    check($code($answer) === 200, json_encode($answer));
    $saved = $read();
    check($saved['detectionImageSize'] === 640 && $saved['confidenceThreshold'] === 0.35 && $saved['processingInterval'] === 10, 'round trip');
    $snapshot = json_decode($db->query("select snapshot from parking_config_revisions where camera_id = 3 and revision = {$saved['revision']}")->fetchColumn(), true);
    check($snapshot['detectionImageSize'] === 640 && $snapshot['confidenceThreshold'] === 0.35 && $snapshot['processingInterval'] === 10, 'snapshot');
    check($code($put($saved['revision'], ['processingInterval' => null])) === 200 && $read()['processingInterval'] === null, 'override removed');
};

$tests['the least interval comes from the backend config'] = function () use ($db, $fixture) {
    $config = ['backends' => ['parking' => ['backend' => 'internal', 'interval' => 120, 'minInterval' => 30]]];
    $strict = new backends\parking\internal($config, $db, null, 'admin');
    check($strict->settings() === ['interval' => 120, 'minInterval' => 30], 'settings');
    $current = $strict->getConfig(3);
    check($strict->saveConfig(3, ['revision' => $current['revision'], 'processingInterval' => 20] + $fixture('cars')) === false && $strict->lastFailure() === 'parkingInvalidConfig', '20 < 30');
    check($strict->saveConfig(3, ['revision' => $current['revision'], 'processingInterval' => 30] + $fixture('cars')) !== false, '30');
    $odd = new backends\parking\internal(['backends' => ['parking' => ['backend' => 'internal', 'interval' => 5, 'minInterval' => 'x']]], $db, null, 'admin');
    check($odd->settings() === ['interval' => 10, 'minInterval' => 10], 'invalid values fall back, interval is not below the minimum');
};

$result = fn ($revision, $extra = []) => $extra + [
    'revision' => $revision,
    'frameWidth' => 1920,
    'frameHeight' => 1080,
    'freeSpaces' => [['parkingAreaId' => 1, 'spaces' => [[['x' => 1, 'y' => 2], ['x' => 3, 'y' => 2], ['x' => 3, 'y' => 4.5], ['x' => 1, 'y' => 4]]]]],
];

$tests['cameras to process are the ready and enabled ones with their effective interval'] = function () use ($parking, $api, $code, $fixture) {
    $find = fn () => array_values(array_filter($parking->camerasToProcess(), fn ($c) => $c['cameraId'] === 3))[0] ?? null;
    $GLOBALS['db']->exec('update parking_configs set processing_interval = null where camera_id = 3');
    $revision = $parking->getConfig(3)['revision'];
    check($find() === ['cameraId' => 3, 'revision' => $revision, 'interval' => 60], 'global interval ' . json_encode($find()));
    check($code($api('PUT', 3, ['revision' => $revision, 'processingInterval' => 45] + $fixture('cars'))) === 200, 'override');
    check($find() === ['cameraId' => 3, 'revision' => $revision + 1, 'interval' => 45], 'override interval');
    check($code($api('PUT', 3, ['revision' => $revision + 1, 'enabled' => 0] + $fixture('cars'))) === 200, 'disable');
    check($find() === null, 'disabled camera is not processed');
    check($code($api('PUT', 3, ['revision' => $revision + 2] + $fixture('no calibration'))) === 200, 'not ready');
    check($find() === null, 'not ready camera is not processed');
    check($code($api('PUT', 3, ['revision' => $revision + 3] + $fixture('cars'))) === 200, 'ready again');
    check($find()['interval'] === 60, 'back to the global interval');
    $strict = new backends\parking\internal(['backends' => ['parking' => ['backend' => 'internal', 'minInterval' => 50]]], $GLOBALS['db'], null, 'admin');
    $GLOBALS['db']->exec('update parking_configs set processing_interval = 20 where camera_id = 3');
    check(array_values(array_filter($strict->camerasToProcess(), fn ($c) => $c['cameraId'] === 3))[0]['interval'] === 50, 'an old override below a raised minimum is lifted to it');
    $GLOBALS['db']->exec('update parking_configs set processing_interval = null where camera_id = 3');
};

$tests['current revision is the snapshot of the saved configuration'] = function () use ($parking) {
    $config = $parking->getConfig(3);
    $revision = $parking->currentRevision(3);
    check($revision['revision'] === $config['revision'] && $revision['ready'] === true, 'revision');
    check($revision['detectionImageSize'] === 320 && $revision['confidenceThreshold'] === 0.5, 'parameters');
    check(count($revision['calibration']['homography']) === 9 && $revision['areas'][0]['parkingAreaId'] === $config['areas'][0]['parkingAreaId'], 'geometry');
    check($parking->currentRevision(2 + 1000) === null, 'no configuration');
};

$tests['occupancy result of the current revision replaces the previous one and its frame'] = function () use ($parking, $files, $result, $count) {
    $revision = $parking->getConfig(3)['revision'];
    check($parking->getResult(3) === null, 'no result yet');
    check($parking->saveResult(3, $result($revision), 'JPEG1') === 'accepted', 'first');
    check(count($files->files) === 1, 'frame stored');
    $stored = array_values($files->files)[0];
    check($stored['metadata']['storage'] === 'tmpfs' && $stored['metadata']['expire'] > time() && $stored['metadata']['parkingCameraId'] === 3, 'storage metadata');
    $read = $parking->getResult(3, true);
    check($read['revision'] === $revision && $read['frameWidth'] === 1920 && $read['frame'] === base64_encode('JPEG1'), 'read back');
    // jsonb doesn't keep the order of keys
    check($read['freeSpaces'] == $result($revision)['freeSpaces'] && $read['processedAt'], 'free spaces');
    check(!array_key_exists('frame', $parking->getResult(3)), 'no frame unless asked');

    check($parking->saveResult(3, $result($revision, ['freeSpaces' => []]), 'JPEG2') === 'accepted', 'second');
    check(count($files->files) === 1 && array_values($files->files)[0]['contents'] === 'JPEG2', 'previous frame removed');
    check($parking->getResult(3, true)['freeSpaces'] === [] && $count('select count(*) from parking_results') === 1, 'replaced');
};

$tests['a result of an outdated revision is discarded'] = function () use ($parking, $files, $result) {
    $revision = $parking->getConfig(3)['revision'];
    check($parking->saveResult(3, $result($revision - 1), 'OLD') === 'discarded', 'outdated');
    check($parking->saveResult(3, $result($revision + 1), 'NEW') === 'discarded', 'future');
    check($parking->saveResult(2 + 1000, $result(1), 'X') === 'discarded', 'no configuration');
    check(count($files->files) === 1 && array_values($files->files)[0]['contents'] === 'JPEG2', 'nothing stored');
    check($parking->getResult(3)['revision'] === $revision, 'previous result kept');
};

$tests['a result for a disabled or not ready current revision is discarded'] = function () use ($parking, $files, $result, $api, $code, $fixture) {
    $revision = $parking->getConfig(3)['revision'];
    check($code($api('PUT', 3, ['revision' => $revision, 'enabled' => 0] + $fixture('cars'))) === 200, 'disable');
    check($parking->saveResult(3, $result($revision + 1), 'OFF') === 'discarded', 'disabled');
    check($code($api('PUT', 3, ['revision' => $revision + 1] + $fixture('no calibration'))) === 200, 'not ready');
    check($parking->saveResult(3, $result($revision + 2), 'NOT READY') === 'discarded', 'not ready');
    check(count($files->files) === 1 && array_values($files->files)[0]['contents'] === 'JPEG2', 'nothing stored');
    check($code($api('PUT', 3, ['revision' => $revision + 2] + $fixture('cars'))) === 200, 'ready again');
    check($parking->saveResult(3, $result($revision + 3), 'JPEG2') === 'accepted', 'accepted again');
    check(count($files->files) === 1, 'one frame');
};

$tests['a failure of the frame storage is an error, not an exception'] = function () use ($parking, $files, $result) {
    $revision = $parking->getConfig(3)['revision'];
    $files->failing = true;
    try {
        check($parking->saveResult(3, $result($revision), 'JPEG') === false && $parking->lastFailure() === 'unknown', 'failed');
        $status = $parking->getStatus(3);
        check($status['status'] === 'saveFailed' && $status['reason'] === 'storage is down' && $status['revision'] === $revision, 'the failure is visible as the status ' . json_encode($status));
        check(internal('result', null, ['cameraId' => 3, 'frame' => base64_encode('JPEG')] + $result($revision))[0] === 500, 'internal API answers 500');
    } finally {
        $files->failing = false;
    }
    check($parking->getResult(3, true)['frame'] === base64_encode('JPEG2'), 'the previous result is kept');
    check($parking->saveResult(3, $result($revision), 'JPEG2') === 'accepted' && $parking->getStatus(3)['status'] === 'ok', 'ok again');
    check($parking->saveStatus(3, 'saveFailed', 'x', $revision) === false, 'saveFailed is set by the server only');
};

$tests['frames go to tmpfs by default, to gridfs without the tmpfs backend'] = function () use ($db, $files, $result) {
    $plain = new backends\parking\internal(['backends' => ['parking' => ['backend' => 'internal']]], $db, null, 'admin');
    $revision = $plain->getConfig(3)['revision'];
    check($plain->saveResult(3, $result($revision), 'JPEG2') === 'accepted' && array_values($files->files)[0]['metadata']['storage'] === 'tmpfs', 'tmpfs');
    $tmpfs = $GLOBALS['testBackends']['tmpfs'];
    unset($GLOBALS['testBackends']['tmpfs']);
    try {
        check($plain->saveResult(3, $result($revision), 'JPEG2') === 'accepted' && array_values($files->files)[0]['metadata']['storage'] === 'gridfs', 'gridfs without tmpfs');
    } finally {
        $GLOBALS['testBackends']['tmpfs'] = $tmpfs;
    }
    $explicit = new backends\parking\internal(['backends' => ['parking' => ['backend' => 'internal', 'storage' => 'tmpfs']]], $db, null, 'admin');
    check($explicit->saveResult(3, $result($revision), 'JPEG2') === 'accepted' && array_values($files->files)[0]['metadata']['storage'] === 'tmpfs', 'explicit tmpfs is kept');
};

$tests['an invalid result is rejected'] = function () use ($parking, $files, $result) {
    $revision = $parking->getConfig(3)['revision'];
    $point = ['x' => 1, 'y' => 1];
    foreach ([
        $result('1'),
        $result($revision, ['frameWidth' => 10]),
        $result($revision, ['freeSpaces' => 'x']),
        $result($revision, ['freeSpaces' => [['parkingAreaId' => '1', 'spaces' => []]]]),
        $result($revision, ['freeSpaces' => [['parkingAreaId' => 1, 'spaces' => [[$point, $point, $point]]]]]),
        $result($revision, ['freeSpaces' => [['parkingAreaId' => 1, 'spaces' => [[$point, $point, $point, ['x' => 'a', 'y' => 1]]]]]]),
    ] as $i => $bad) {
        check($parking->saveResult(3, $bad, 'JPEG') === false && $parking->lastFailure() === 'parkingInvalidResult', "invalid $i");
    }
    check($parking->saveResult(3, $result($revision), '') === false, 'empty frame');
    check(count($files->files) === 1, 'nothing stored');
};

$tests['the result API returns the latest result with its frame'] = function () use ($parking, $files) {
    $answer = api\cameras\parkingResult::GET(['_id' => '3']);
    check($answer[200]['result']['frame'] === base64_encode('JPEG2'), 'frame');
    check(api\cameras\parkingResult::GET(['_id' => '2'])[200]['result'] === null, 'no result');
    $kept = $files->files;
    $files->files = [];
    check(api\cameras\parkingResult::GET(['_id' => '3'])[200]['result']['frame'] === null, 'a lost frame is null');
    $files->files = $kept;
    check(api\cameras\parkingResult::index()['GET'] === '#same(addresses,house,GET)', 'rights');
};

$tests['the storage of frames comes from the backend config'] = function () use ($db, $files, $result) {
    $gridfs = new backends\parking\internal(['backends' => ['parking' => ['backend' => 'internal', 'storage' => 'gridfs']]], $db, null, 'admin');
    $revision = $gridfs->getConfig(3)['revision'];
    check($gridfs->saveResult(3, $result($revision), 'JPEG3') === 'accepted', 'saved');
    check(array_values($files->files)[0]['metadata']['storage'] === 'gridfs', 'gridfs');
};

$tests['processing status: ok with an accepted result, failures keep the result'] = function () use ($parking, $files, $result, $api, $code, $fixture) {
    $revision = $parking->getConfig(3)['revision'];
    $status = $parking->getStatus(3);
    check($status['status'] === 'ok' && $status['revision'] === $revision && $status['reason'] === '' && $status['time'], 'ok after the accepted result ' . json_encode($status));
    $before = $parking->getResult(3, true);

    foreach (['cameraUnavailable', 'detectionFailed', 'stale'] as $failure) {
        check($parking->saveStatus(3, $failure, "why $failure", $revision) === true, $failure);
        $status = $parking->getStatus(3);
        check($status['status'] === $failure && $status['reason'] === "why $failure" && $status['revision'] === $revision, "stored $failure");
    }
    check($parking->getResult(3, true) == $before, 'a failure never erases the latest result');
    check(count($files->files) === 1, 'nor its frame');

    foreach ([['ok', 'x', $revision], ['notProcessed', 'x', $revision], ['bad', 'x', $revision], ['stale', null, $revision], ['stale', 'x', '1']] as $bad) {
        check($parking->saveStatus(3, ...$bad) === false && $parking->lastFailure() === 'parkingInvalidStatus', 'invalid ' . json_encode($bad));
    }
    check($parking->saveStatus(3, 'stale', str_repeat('x', 2000), $revision) && mb_strlen($parking->getStatus(3)['reason']) === 1024, 'long reason is cut');
    check($parking->saveStatus(1003, 'stale', 'x', 1) === true && $parking->getStatus(1003) === null, 'no configuration');

    check($parking->saveResult(3, $result($revision), 'JPEG6') === 'accepted' && $parking->getStatus(3)['status'] === 'ok', 'ok again');
    check($parking->saveResult(3, $result($revision - 1), 'OLD') === 'discarded' && $parking->getStatus(3)['status'] === 'ok', 'a discarded result changes nothing');

    // not processed at all: derived from the configuration, over the stored status
    check($code($api('PUT', 3, ['revision' => $revision, 'enabled' => 0] + $fixture('cars'))) === 200, 'disable');
    $status = $parking->getStatus(3);
    check($status['status'] === 'notProcessed' && $status['reason'] === 'disabled' && $status['time'] && $status['revision'] === null, 'disabled ' . json_encode($status));
    check($code($api('PUT', 3, ['revision' => $revision + 1] + $fixture('no calibration'))) === 200, 'not ready');
    check($parking->getStatus(3)['reason'] === 'notReady', 'not ready');
    check($code($api('PUT', 3, ['revision' => $revision + 2] + $fixture('cars'))) === 200, 'ready again');
    check($parking->getStatus(3)['status'] === 'ok', 'the stored status is back');
    check($parking->saveResult(3, $result($revision + 3), 'JPEG2') === 'accepted', 'result for the current revision');
};

$tests['processing status before the first attempt and through the APIs'] = function () use ($parking, $api, $code, $fixture) {
    check($code($api('PUT', 2, ['revision' => $parking->getConfig(2)['revision'] ?? 0] + $fixture('cars'))) === 200, 'camera 2');
    check($parking->getStatus(2) === null, 'no attempt yet');
    check(api\cameras\parkingResult::GET(['_id' => '2'])[200] === ['result' => null, 'status' => null], 'API without anything');
    $revision = $parking->getConfig(3)['revision'];
    check(internal('status', null, ['cameraId' => 3, 'revision' => $revision, 'status' => 'cameraUnavailable', 'reason' => 'timeout'])[0] === 200, 'internal status');
    $answer = api\cameras\parkingResult::GET(['_id' => '3'])[200];
    check($answer['status']['status'] === 'cameraUnavailable' && $answer['status']['reason'] === 'timeout' && $answer['result']['revision'] === $revision, 'API with both');
    check(internal('status', null, ['cameraId' => 3, 'revision' => $revision, 'status' => 'ok'])[0] === 400, 'ok is not reported this way');
    check(internal('status', null, ['cameraId' => 0, 'revision' => $revision, 'status' => 'stale'])[0] === 400, 'camera');
};

$tests['internal API for the occupancy service'] = function () use ($parking, $files, $result) {
    [$status, $cameras] = internal('cameras');
    check($status === 200 && in_array(3, array_column($cameras, 'cameraId')), 'cameras');
    [$status, $revision] = internal('revision', '3');
    check($status === 200 && $revision['revision'] === $parking->getConfig(3)['revision'] && isset($revision['areas']), 'revision');
    check(internal('revision', 'x')[0] === 400 && internal('revision', '0')[0] === 400 && internal('revision', '1003')[0] === 404, 'revision errors');
    $body = ['cameraId' => 3, 'frame' => base64_encode('JPEG5')] + $result($revision['revision']);
    check(internal('result', null, $body)[0] === 200, 'accepted');
    check(array_values($files->files)[0]['contents'] === 'JPEG5', 'stored');
    check(internal('result', null, ['revision' => $revision['revision'] - 1] + $body)[0] === 204, 'discarded');
    check(internal('result', null, ['frame' => '***'] + $body)[0] === 400, 'frame is not base64');
    check(internal('result', null, ['freeSpaces' => 1] + $body)[0] === 400, 'invalid result');
    check(internal('result', null, ['cameraId' => 'x'] + $body)[0] === 400, 'camera');
    $saved = $GLOBALS['testBackends'];
    $GLOBALS['testBackends'] = [];
    try {
        check(internal('cameras')[0] === 404 && internal('result', null, $body)[0] === 404, 'no parking backend');
    } finally {
        $GLOBALS['testBackends'] = $saved;
    }
};

$tests['deleting a configuration removes its result and frame'] = function () use ($parking, $files, $count, $result, $fixture, $api, $code) {
    check($parking->deleteConfig(3), 'delete');
    check($count('select count(*) from parking_results where camera_id = 3') === 0 && count($files->files) === 0, 'removed');
    check($count('select count(*) from parking_statuses where camera_id = 3') === 0, 'status removed');
    check($code($api('PUT', 3, ['revision' => 0] + $fixture('cars'))) === 200, 'configured again');
    check($parking->saveResult(3, $result(1), 'JPEG4') === 'accepted', 'result for the new configuration');
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
        "update parking_configs set detection_image_size = 1025 where parking_config_id = $configId",
        "update parking_configs set confidence_threshold = 1.5 where parking_config_id = $configId",
        "update parking_configs set processing_interval = 0 where parking_config_id = $configId",
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
    $GLOBALS['db']->exec("insert into parking_results (camera_id, parking_config_id, revision, processed_at, frame_uuid, frame_width, frame_height, free_spaces) select 1, parking_config_id, revision, now(), 'x', 1920, 1080, '[]' from parking_configs where camera_id = 1");
    check($count('select count(*) from parking_results where camera_id = 1') === 1, 'result row');
    check($cameras->deleteCamera(1) !== false, 'deleteCamera');
    check($count('select count(*) from parking_configs where camera_id = 1') === 0, 'config');
    check($count('select count(*) from parking_results where camera_id = 1') === 0, 'result');
    check($count('select count(*) from parking_config_revisions where camera_id = 1') === 0, 'revisions');
    check($count('select count(*) from parking_areas a join parking_configs c using (parking_config_id) where c.camera_id = 1') === 0, 'areas');
    check($count('select count(*) from parking_areas') === 2, 'areas of other cameras are kept');
    check($count('select count(*) from parking_configs') === 2, 'other configurations are kept');
};

$tests['DELETE removes a configuration'] = function () use ($api, $code, $count) {
    check($code($api('DELETE', 2)) === 200, 'DELETE');
    check($api('GET', 2)[200]['parking'] === null, 'GET after DELETE');
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
