<?php
// Offline tests: all camera I/O is replaced by a documented CGI fixture.
require_once __DIR__ . '/../../server/hw/autoload.php';
require_once __DIR__ . '/../../server/utils/functions.php';
require_once __DIR__ . '/../../server/utils/polyfills.php';
require_once __DIR__ . '/../../server/utils/loader.php';

use hw\ip\camera\dahua\dahua;
use hw\ip\camera\dahua\MotionGrid;
use hw\ip\camera\entities\DetectionZone;
use hw\SmartConfigurator\DbConfigCollector\CameraDbConfigCollector;
use hw\SmartConfigurator\SmartConfigurator;

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    $checks++;
    if (!$condition) throw new RuntimeException($message);
}
function rejects(callable $fn, string $message): void {
    try { $fn(); } catch (InvalidArgumentException|UnexpectedValueException|LogicException|RuntimeException $e) {
        check(true, $message); return;
    }
    check(false, $message);
}

class FakeDahua extends dahua {
    public array $state = [];
    public array $writes = [];
    public bool $ignoreWrites = false;
    public string $ack = 'OK';
    public function __construct() {
        parent::__construct('https://operator@192.0.2.10:8443', 'fixture-secret', false, true);
        $this->state['MotionDetect[0].Enable'] = 'false';
        foreach (MotionGrid::fromZones([]) as $i => $rows) {
            foreach ($rows as $j => $mask) $this->state["MotionDetect[0].MotionDetectWindow[$i].Region[$j]"] = (string)$mask;
            $this->state["MotionDetect[0].MotionDetectWindow[$i].Sensitive"] = '58';
            $this->state["MotionDetect[0].MotionDetectWindow[$i].Threshold"] = '4';
        }
        for ($j = 0; $j < 18; $j++) $this->state["MotionDetect[0].Region[$j]"] = '0';
        $this->state['MotionDetect[0].EventHandler.TimeSection[0][0]'] = '1 00:00:00-24:00:00';
    }
    protected function apiCall(string $resource, array $params = []): string {
        if ($resource === 'snapshot.cgi') return "\xff\xd8fixture\xff\xd9";
        if ($resource === 'magicBox.cgi' && ($params['action'] ?? '') === 'getSystemInfo') {
            return "serialNumber=fixture-id\r\ndeviceType=IPC-HFW1420S\r\nhardwareVersion=1.0\r\n";
        }
        if ($resource !== 'configManager.cgi') throw new RuntimeException('Unexpected API resource');
        if ($params['action'] === 'setConfig') {
            $this->writes[] = $params;
            if (!$this->ignoreWrites) foreach ($params as $key => $value) {
                if ($key !== 'action') $this->state[$key] = (string)$value;
            }
            return $this->ack;
        }
        return implode("\r\n", array_map(fn($key, $value) => "table.$key=$value", array_keys($this->state), $this->state));
    }
}

$full = [new DetectionZone(0, 0, 100, 100)];
$masks = MotionGrid::fromZones($full);
check($masks[0] === array_fill(0, 18, 4194303), 'Full image mask');
check($masks[1] === array_fill(0, 18, 0), 'Unused window cleared');
$corner = MotionGrid::fromZones([new DetectionZone(0, 0, 100 / 22, 100 / 18)]);
check($corner[0][0] === 1 && $corner[0][1] === 0, 'Top-left bit and row');
$corner = MotionGrid::fromZones([new DetectionZone(2100 / 22, 1700 / 18, 100 / 22, 100 / 18)]);
check($corner[0][17] === (1 << 21) && $corner[0][16] === 0, 'Bottom-right bit and row');
foreach ([new DetectionZone(-1, 0, 10, 10), new DetectionZone(99, 0, 2, 2), new DetectionZone(0, 0, 0, 5),
    new DetectionZone(NAN, 0, 1, 1), new DetectionZone(INF, 0, 1, 1)] as $invalid) {
    rejects(fn() => MotionGrid::fromZones([$invalid]), 'Reject invalid rectangle');
}
rejects(fn() => MotionGrid::fromZones(array_fill(0, 5, $full[0])), 'Reject fifth zone');
check(MotionGrid::canonical($masks) == [new DetectionZone(0, 0, 22, 18)], 'Canonical full rectangle');
check(MotionGrid::canonical(MotionGrid::fromZones([$full[0], $full[0]])) == MotionGrid::canonical($masks), 'Overlaps are stable');
for ($sample = 0; $sample < 100; $sample++) {
    $left = $sample % 20;
    $top = $sample % 16;
    $zones = [new DetectionZone($left * 100 / 22, $top * 100 / 18, 200 / 22, 200 / 18)];
    $canonical = MotionGrid::canonical(MotionGrid::fromZones($zones));
    check($canonical == [new DetectionZone($left, $top, 2, 2)], 'Grid boundary round trip');
}

$camera = new FakeDahua();
check($camera->url === 'https://192.0.2.10:8443' && $camera->login === 'operator', 'Username separated from URL');
check($camera->ping() && $camera->getSysinfo()['DeviceModel'] === 'IPC-HFW1420S', 'Device information');
check(str_starts_with($camera->getCamshot(), "\xff\xd8"), 'Snapshot');
check($camera->getConfig()['motionDetection'] === [], 'Disabled motion');
$camera->configureMotionDetection($full);
check($camera->state['MotionDetect[0].Enable'] === 'true', 'Enable motion');
check($camera->state['MotionDetect[0].Region[0]'] === '4194303', 'Legacy union updated');
check($camera->state['MotionDetect[0].MotionDetectWindow[0].Sensitive'] === '58', 'Preserve sensitivity');
check(!isset($camera->writes[0]['MotionDetect[0].EventHandler.TimeSection[0][0]']), 'Preserve schedule/actions');
check($camera->getConfig()['motionDetection'] == [new DetectionZone(0, 0, 22, 18)], 'Read-back normalizes bitmap');
$camera->configureMotionDetection([]);
check($camera->state['MotionDetect[0].Enable'] === 'false', 'Disable motion');
check($camera->state['MotionDetect[0].Region[0]'] === '0', 'Disabled legacy grid cleared');
check(MotionGrid::readWindows($camera->state) === MotionGrid::fromZones([]), 'All stale windows cleared');

$camera = new FakeDahua();
unset($camera->state['MotionDetect[0].MotionDetectWindow[3].Region[17]']);
rejects(fn() => $camera->configureMotionDetection($full), 'Unknown grid fails before write');
check($camera->writes === [], 'No writes for unknown grid');
$camera = new FakeDahua();
$camera->state['MotionDetect[0].MotionDetectWindow[0].Region[0]'] = '4294967295';
rejects(fn() => $camera->configureMotionDetection($full), 'Reject wider grids');
$camera = new FakeDahua();
$camera->ignoreWrites = true;
rejects(fn() => $camera->configureMotionDetection($full), 'Detect ignored write even after OK');
$camera = new FakeDahua();
$camera->ack = 'Error';
rejects(fn() => $camera->configureMotionDetection($full), 'Reject failed write');
rejects(fn() => dahua::parseConfig('<html>login</html>'), 'Reject login HTML');
check(dahua::parseConfig("table.Value=a=b\r\n")['Value'] === 'a=b', 'Preserve equals in values');
rejects(fn() => new dahua('http://admin:secret@192.0.2.10', 'x', false, true), 'Reject password in URL');
rejects(fn() => new dahua('file:///tmp/camera', 'x', false, true), 'Reject non-HTTP URL');
rejects(fn() => new dahua('http://192.0.2.10', 'x', true, true), 'No first-time password changes');
$loaded = loadDevice('camera', 'dahua.json', 'http://192.0.2.10', 'x', false, true);
check($loaded instanceof dahua, 'Model loader resolves the new driver');

// Exercise the real SmartConfigurator, including empty push-server configuration.
$camera = new FakeDahua();
$collector = new CameraDbConfigCollector(['ntp_servers' => ['ntp://192.0.2.1:123']], [
    'json' => ['eventServer' => false], 'name' => 'Do not overwrite OSD', 'timezone' => 'Europe/Moscow',
    'mdArea' => [(object)['x' => 13.3, 'y' => 21.2, 'w' => 33.3, 'h' => 24.7]],
]);
check($collector->collectConfig()['eventServer'] === '', 'Pull cameras need no syslog configuration');
$pushCollector = new CameraDbConfigCollector([
    'ntp_servers' => ['ntp://192.0.2.1:123'], 'syslog_servers' => ['beward' => ['udp://192.0.2.2:45450']],
], ['json' => ['eventServer' => 'beward'], 'name' => 'Existing camera', 'timezone' => 'Europe/Moscow']);
check($pushCollector->collectConfig()['eventServer'] === 'udp://192.0.2.2:45450', 'Existing push-server selection unchanged');
ob_start();
$configurator = new SmartConfigurator($camera, $collector);
$configurator->makeConfiguration();
$output = ob_get_clean();
check(count($camera->writes) === 1, 'Autoconfiguration applies motion exactly once');
check(!str_contains($output, 'DIFFERENCE DETECTED'), 'Grid quantization has no persistent difference');
ob_start();
$configurator->makeConfiguration();
$output = ob_get_clean();
check(str_contains($output, 'Nothing to reconfigure') && count($camera->writes) === 1, 'Idempotent autoconfiguration');
$camera->configureMotionDetection([new DetectionZone(5, 5, 15, 15), new DetectionZone(65, 65, 25, 25)]);
check($camera->state['MotionDetect[0].MotionDetectWindow[1].Region[12]'] !== '0', 'Second zone applied');
$camera->configureMotionDetection([new DetectionZone(5, 5, 15, 15)]);
check(MotionGrid::readWindows($camera->state)[1] === array_fill(0, 18, 0), 'Removed zone does not remain active');
echo "PASS $checks Dahua driver checks (offline)\n";
