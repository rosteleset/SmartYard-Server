<?php

namespace VirtualIntercom;

use RuntimeException;
use Throwable;

/** Call capabilities stay on the server. The guest never receives resident credentials. */
final class Service
{
    private const TTL = 300;
    private const REGISTRATION_GRACE = 3600;
    private const TERMINAL = ['ended', 'cancelled', 'failed'];

    public function __construct(private $redis, private $db, private $houses, private array $settings, private PanelRepository $panels)
    {
    }

    public static function configured(): self
    {
        global $redis, $db, $config;
        $file = __DIR__ . '/../../client/config/config.json';
        $client = is_file($file) ? \json5_decode(file_get_contents($file), true) : [];
        require_once __DIR__ . '/PanelRepository.php';
        return new self($redis, $db, \loadBackend('households'), self::connectionSettings($config, $client), new PanelRepository($db));
    }

    private static function connectionSettings(array $config, array $client): array
    {
        $url = parse_url($config['api']['frontend'] ?? '');
        $origin = ($url['scheme'] ?? '') === 'https' && !empty($url['host'])
            ? 'https://' . strtolower($url['host']) . (isset($url['port']) && $url['port'] !== 443 ? ':' . $url['port'] : '') : '';
        $sip = $client['asterisk'] ?? [];
        return ['origin' => $origin, 'sipDomain' => $sip['sipDomain'] ?? '', 'ws' => $sip['ws'] ?? '',
            'iceServers' => $sip['ice'] ?? [], 'turn' => $config['backends']['sip']['turn'] ?? [],
            'enabled' => $origin !== '' && !empty($sip['sipDomain']) && parse_url($sip['ws'] ?? '', PHP_URL_SCHEME) === 'wss'];
    }

    private function fail(string $message = 'Недоступно', int $code = 403): never
    {
        throw new RuntimeException($message, $code);
    }

    private function panel(string $slug): array
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{12}$/D', $slug)) $this->fail('Панель не найдена', 404);
        $panel = $this->panels->bySlug($slug);
        if (empty($this->settings['enabled']) || !$panel || empty($panel['enabled'])) {
            $this->fail('Виртуальный домофон недоступен', 404);
        }
        return $panel;
    }

    /** Called only by the authenticated RBT management API. */
    public function panelSettings(int $entranceId): array
    {
        $entrance = $entranceId > 0 ? $this->houses->getEntrance($entranceId) : false;
        if (!$entrance) $this->fail('Вход не найден', 404);
        $panel = $this->panels->byEntrance($entranceId);
        return ['entranceId' => $entranceId, 'available' => !empty($this->settings['enabled']),
            'enabled' => $panel['enabled'] ?? false, 'title' => $panel['title'] ?? $entrance['entrance'],
            'subtitle' => $panel['subtitle'] ?? 'Выберите, кому позвонить',
            'listEnabled' => $panel['listEnabled'] ?? true,
            'allowAllFlats' => $panel['allowAllFlats'] ?? true,
            'url' => $panel ? rtrim($this->settings['origin'], '/') . '/v/' . rawurlencode($panel['slug']) : null];
    }

    public function savePanel(int $entranceId, array $input): array
    {
        $current = $this->panelSettings($entranceId);
        foreach (['enabled', 'listEnabled', 'allowAllFlats'] as $key) {
            if (!isset($input[$key]) || !is_bool($input[$key])) $this->fail('Некорректные настройки панели', 400);
        }
        foreach (['title' => 120, 'subtitle' => 240] as $key => $limit) {
            if (!isset($input[$key]) || !is_string($input[$key]) || mb_strlen($input[$key]) > $limit) $this->fail('Слишком длинное название или описание панели', 400);
            $input[$key] = trim($input[$key]);
        }
        if ($input['title'] === '') $this->fail('Укажите название панели', 400);
        if ($input['enabled']) {
            if (!$current['available']) $this->fail('Виртуальные домофоны отключены на сервере', 409);
            $entrance = $this->houses->getEntrance($entranceId);
            $device = $this->houses->getDomophone((int)$entrance['domophoneId']);
            if ($entrance['domophoneOutput'] === null || !$device || empty($device['enabled']) || $device['model'] === 'dummy.json') {
                $this->fail('Сначала назначьте входу включённый физический домофон и выход реле', 409);
            }
        }
        // This must not call modifyEntrance(): it queues physical reconfiguration.
        $this->panels->save($entranceId, $input);
        return $this->panelSettings($entranceId);
    }

    public function metadata(string $slug): array
    {
        $panel = $this->panel($slug);
        $entrance = $this->houses->getEntrance((int)$panel['entranceId']);
        if (!$entrance) {
            $this->fail('Вход недоступен', 404);
        }
        $query = $this->db->prepare("SELECT ef.house_flat_id, ef.apartment, f.flat, label.value AS public_name, calls.value AS virtual_calls
            FROM houses_entrances_flats ef JOIN houses_flats f USING (house_flat_id)
            LEFT JOIN custom_fields_values label ON label.apply_to = 'flat' AND label.id = f.house_flat_id AND label.field = :name_field
            LEFT JOIN custom_fields_values calls ON calls.apply_to = 'flat' AND calls.id = f.house_flat_id AND calls.field = :calls_field
            WHERE ef.house_entrance_id = :entrance ORDER BY ef.apartment, f.flat");
        $query->execute(['entrance' => $panel['entranceId'], 'name_field' => PanelRepository::FLAT_NAME, 'calls_field' => PanelRepository::FLAT_CALLS]);
        $flats = [];
        foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if (($panel['allowAllFlats'] ?? true) !== true && ($row['virtual_calls'] ?? null) !== '1') continue;
            $name = trim(preg_replace('/\s+/u', ' ', $row['public_name'] ?? ''));
            $flats[] = ['id' => (int)$row['house_flat_id'], 'number' => (string)($row['apartment'] ?: $row['flat']),
                'name' => mb_substr($name, 0, 120)];
        }
        return ['title' => $panel['title'] ?? $entrance['entrance'], 'subtitle' => $panel['subtitle'] ?? 'Выберите, кому позвонить',
                'flats' => $flats, 'listEnabled' => $panel['listEnabled'] ?? true];
    }

    public function checkOrigin(?string $origin): void
    {
        if (!$origin || $origin !== ($this->settings['origin'] ?? null)) {
            $this->fail('Недопустимый источник запроса');
        }
    }

    public function limit(string $name, int $maximum, int $seconds): void
    {
        $key = 'VI:RATE:' . hash('sha256', $name) . ':' . intdiv(time(), $seconds);
        $count = $this->redis->eval("local n=redis.call('INCR',KEYS[1]); if n==1 then redis.call('EXPIRE',KEYS[1],ARGV[1]) end; return n", [$key, $seconds + 1], 1);
        if ($count > $maximum) {
            $this->fail('Слишком много попыток. Подождите немного.', 429);
        }
    }

    private function access(array $session, ?int $deviceId = null): array
    {
        $panel = $this->panel($session['panel']);
        if ((int)$panel['entranceId'] !== (int)$session['entranceId']) {
            $this->fail();
        }
        if (($panel['allowAllFlats'] ?? true) !== true && !$this->panels->flatCallsEnabled((int)$session['flatId'])) {
            $this->fail('Звонки с виртуального домофона по этому номеру не разрешены');
        }
        $flat = $this->houses->getFlat($session['flatId']);
        if (!$flat || !empty($flat['autoBlock']) || !empty($flat['manualBlock']) || !empty($flat['adminBlock'])) {
            $this->fail('Вызов по этому номеру недоступен');
        }
        $entrance = $this->houses->getEntrance($session['entranceId']);
        $linked = false;
        foreach ($flat['entrances'] ?? [] as $item) {
            if ((int)$item['entranceId'] === (int)$session['entranceId']) {
                $linked = true;
            }
        }
        if (!$linked || !$entrance || $entrance['domophoneOutput'] === null) {
            $this->fail('Выбранная квартира или офис не относится к этому входу');
        }
        $domophone = $this->houses->getDomophone((int)$entrance['domophoneId']);
        if (!$domophone || empty($domophone['enabled']) || $domophone['model'] === 'dummy.json') {
            $this->fail('Домофон недоступен');
        }
        // Do not change the target of a call when an administrator reassigns an entrance.
        if (isset($session['domophoneId']) && ((int)$session['domophoneId'] !== (int)$entrance['domophoneId'] ||
            (int)$session['output'] !== (int)$entrance['domophoneOutput'])) {
            $this->fail('Настройки входа изменились');
        }
        $devices = [];
        $memberships = [];
        foreach ($this->houses->getDevices('flat', (int)$session['flatId']) as $device) {
            if ($deviceId !== null && (int)$device['deviceId'] !== $deviceId) {
                continue;
            }
            if (empty($device['voipEnabled']) || $device['platform'] === null) {
                continue;
            }
            $enabled = false;
            foreach ($device['flats'] as $f) {
                if ((int)$f['flatId'] === (int)$session['flatId'] && (int)$f['voipEnabled'] === 1) {
                    $enabled = true;
                }
            }
            // Device-flat mapping alone is not proof of the subscriber's current membership.
            $subscriberId = (int)$device['subscriberId'];
            if (!array_key_exists($subscriberId, $memberships)) {
                $memberships[$subscriberId] = false;
                foreach ($this->houses->getSubscribers('id', $subscriberId, ['withoutHouses']) as $subscriber) {
                    foreach ($subscriber['flats'] ?? [] as $f) {
                        if ((int)$f['flatId'] === (int)$session['flatId']) {
                            $memberships[$subscriberId] = true;
                        }
                    }
                }
            }
            $token = in_array((int)$device['tokenType'], [1, 2], true) ? $device['voipToken'] : $device['pushToken'];
            if ($enabled && $memberships[$subscriberId] && is_string($token) && $token !== '') {
                $devices[] = $device;
            }
        }
        if (!$devices) {
            $this->fail('Нет доступных устройств для вызова', 409);
        }
        return [$entrance, $domophone, $devices];
    }

    private function read(string $id): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) {
            $this->fail('Сессия не найдена', 404);
        }
        $raw = $this->redis->get('VI:SESSION:' . $id);
        if (!$raw) {
            $this->fail('Сессия завершена', 410);
        }
        return json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    }

    private function save(array $session): void
    {
        $this->redis->setex('VI:SESSION:' . $session['id'], max(1, $session['expires'] - time() + 60), json_encode($session, JSON_THROW_ON_ERROR));
    }

    private function mutate(string $id, callable $operation): mixed
    {
        $key = 'VI:LOCK:' . $id;
        $token = bin2hex(random_bytes(16));
        $locked = false;
        for ($i = 0; $i < 40; $i++) {
            if ($this->redis->set($key, $token, ['nx', 'ex' => 15])) {
                $locked = true;
                break;
            }
            usleep(25000);
        }
        if (!$locked) {
            $this->fail('Попробуйте ещё раз', 409);
        }
        try {
            $session = $this->read($id);
            $result = $operation($session);
            // Do not overwrite newer state if this lock expired during I/O.
            $saved = $this->redis->eval("if redis.call('GET',KEYS[1])~=ARGV[1] then return 0 end; redis.call('SETEX',KEYS[2],ARGV[2],ARGV[3]); return 1",
                [$key, 'VI:SESSION:' . $id, $token, max(1, $session['expires'] - time() + 60), json_encode($session, JSON_THROW_ON_ERROR)], 2);
            if (!$saved) $this->fail('Попробуйте ещё раз', 409);
            return $result;
        } finally {
            $this->redis->eval("if redis.call('GET',KEYS[1])==ARGV[1] then return redis.call('DEL',KEYS[1]) end; return 0", [$key, $token], 1);
        }
    }

    public function create(string $slug, int $flatId, string $ip): array
    {
        $panel = $this->panel($slug);
        $this->limit('create-ip:' . $ip, 4, 60);
        $this->limit('create-ip-hour:' . $ip, 30, 3600);
        $this->limit('create-flat:' . $flatId, 4, 60);
        $session = ['id' => bin2hex(random_bytes(16)), 'panel' => $slug, 'flatId' => $flatId,
                    'entranceId' => (int)$panel['entranceId'], 'created' => time(), 'expires' => time() + self::TTL,
                    'status' => 'created', 'doorStatus' => 'idle', 'legs' => [], 'guestPassword' => bin2hex(random_bytes(24)),
                    'guestToken' => bin2hex(random_bytes(32)), 'previewHash' => bin2hex(random_bytes(24))];
        [$entrance, $domophone, $devices] = $this->access($session);
        $session['domophoneId'] = (int)$entrance['domophoneId'];
        $session['output'] = (int)$entrance['domophoneOutput'];
        $session['deviceIds'] = array_map(fn($d) => (int)$d['deviceId'], $devices);
        $session['title'] = $panel['title'] ?? $entrance['entrance'];
        $session['flatNumber'] = (string)$flatId;
        foreach ($this->metadata($slug)['flats'] as $flat) {
            if ($flat['id'] === $flatId) {
                $session['flatNumber'] = $flat['number'];
            }
        }
        $this->save($session);
        $ice = $this->settings['iceServers'] ?? [];
        $credentials = null;
        // Reuse the existing browser ICE list. Only TURN entries without
        // configured credentials need a temporary, visitor-specific identity.
        foreach ($ice as &$server) {
            $turnUrls = preg_grep('/^turns?:/', (array)($server['urls'] ?? []));
            if (!$turnUrls || (isset($server['username']) && isset($server['credential']))) continue;
            if ($credentials === null) {
                $turn = $this->settings['turn'] ?? [];
                if (!empty($turn['secret'])) {
                    $username = $session['expires'] . ':' . $session['id'];
                    $password = base64_encode(hash_hmac('sha1', $username, $turn['secret'], true));
                } else {
                    $username = 'vi_' . $session['id'];
                    $realm = $turn['realm'] ?? 'rbt';
                    $password = bin2hex(random_bytes(24));
                    $this->redis->setex("turn/realm/$realm/user/$username/key", self::TTL, md5("$username:$realm:$password"));
                }
                $credentials = ['username' => $username, 'credential' => $password];
            }
            $server = array_replace($server, $credentials);
        }
        unset($server);
        return ['id' => $session['id'], 'token' => $session['guestToken'], 'expires' => $session['expires'],
                'sip' => ['username' => 'vi_' . $session['id'], 'password' => $session['guestPassword'],
                          'domain' => $this->settings['sipDomain'], 'ws' => $this->settings['ws'], 'iceServers' => $ice]];
    }

    public function guest(string $id, string $token): array
    {
        $s = $this->read($id);
        if (!$token || !hash_equals($s['guestToken'], $token)) {
            $this->fail();
        }
        return $s;
    }

    public function status(string $id, string $token): array
    {
        $s = $this->guest($id, $token);
        return ['status' => $s['expires'] <= time() ? 'ended' : $s['status'], 'doorStatus' => $s['doorStatus'],
                'reason' => $s['reason'] ?? null];
    }

    public function frame(string $id, string $token, string $jpeg): void
    {
        $s = $this->guest($id, $token);
        if (in_array($s['status'], self::TERMINAL, true) || $s['expires'] <= time()) {
            $this->fail('Сессия завершена', 410);
        }
        $this->limit('frame:' . $id, 2, 1);
        if (strlen($jpeg) > 180000 || !str_starts_with($jpeg, "\xff\xd8")) {
            $this->fail('Некорректный кадр', 422);
        }
        $size = @getimagesizefromstring($jpeg);
        if (!$size || $size[2] !== IMAGETYPE_JPEG || $size[0] > 1280 || $size[1] > 1280) {
            $this->fail('Некорректный кадр', 422);
        }
        $this->mutate($id, function (&$s) use ($jpeg) {
            if (in_array($s['status'], self::TERMINAL, true) || $s['expires'] <= time()) $this->fail('Сессия завершена', 410);
            $memfs = \loadBackend('memfs');
            if ($memfs) {
                $memfs->putFile($s['previewHash'], $jpeg);
            } else {
                $this->redis->setex('shot_' . $s['previewHash'], self::TTL, $jpeg);
            }
            $this->redis->setex('live_' . $s['previewHash'], self::TTL, $jpeg);
        });
    }

    public function cancel(string $id, string $token): void
    {
        $this->guest($id, $token);
        $this->mutate($id, function (&$s) {
            if (!in_array($s['status'], self::TERMINAL, true)) {
                $s['status'] = 'cancelled';
                $this->clearPreview($s);
            }
        });
    }

    public function endpoint(string $extension, string $section): array|false
    {
        if (!preg_match('/^vi_([a-f0-9]{32})$/D', $extension, $match)) {
            return false;
        }
        try {
            $s = $this->read($match[1]);
            $this->panel($s['panel']);
            if ($s['expires'] <= time() || in_array($s['status'], self::TERMINAL, true)) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }
        return match ($section) {
            'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes', 'maximum_expiration' => '300'],
            'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $s['guestPassword']],
            'endpoints' => ['id' => $extension, 'auth' => $extension, 'aors' => $extension, 'callerid' => 'Virtual visitor',
                            'context' => 'virtual-intercom', 'disallow' => 'all', 'allow' => 'alaw,h264', 'webrtc' => 'yes',
                            'direct_media' => 'no', 'dtmf_mode' => 'rfc4733', 'force_rport' => 'yes', 'rewrite_contact' => 'yes',
                            'rtp_symmetric' => 'yes', 'allow_transfer' => 'no', 'allow_subscribe' => 'no', 'timers' => 'no',
                            'rtp_timeout' => '30', 'rtp_timeout_hold' => '30'],
            default => false,
        };
    }

    public function mobileEndpoint(string $extension, string $section): array|false
    {
        if (!preg_match('/^2[0-9]{9}$/D', $extension)) return false;
        $credential = $this->redis->get('VI:AUTH:' . $extension);
        if (!$credential) return false;
        // Authentication may outlive a call so a delayed REGISTER does not
        // trigger Fail2ban. It grants no outbound calling or door capability.
        return match ($section) {
            'aors' => ['id' => $extension, 'max_contacts' => '1', 'remove_existing' => 'yes', 'maximum_expiration' => '300'],
            'auths' => ['id' => $extension, 'username' => $extension, 'auth_type' => 'userpass', 'password' => $credential],
            'endpoints' => ['id' => $extension, 'auth' => $extension, 'outbound_auth' => $extension, 'aors' => $extension,
                'callerid' => $extension, 'context' => 'virtual-intercom-resident', 'disallow' => 'all', 'allow' => 'alaw,h264',
                'rtp_symmetric' => 'yes', 'force_rport' => 'yes', 'rewrite_contact' => 'yes', 'timers' => 'no',
                'direct_media' => 'no', 'allow_subscribe' => 'no', 'allow_transfer' => 'no', 'dtmf_mode' => 'rfc4733', 'ice_support' => 'yes'],
            default => false,
        };
    }

    /** Only reachable through the existing loopback-only Asterisk entrypoint. */
    public function internal(string $action, array $p): array
    {
        $id = (string)($p['id'] ?? '');
        if ($action === 'begin') {
            if (!preg_match('/^vi_([a-f0-9]{32})$/D', $p['endpoint'] ?? '', $match)) {
                $this->fail();
            }
            $id = $match[1];
        }
        $result = $this->mutate($id, function (&$s) use ($action, $p) {
            if ($action === 'end') {
                if (($p['uniqueid'] ?? '') !== ($s['guestUniqueid'] ?? null)) {
                    $this->fail();
                }
                if (!in_array($s['status'], self::TERMINAL, true)) {
                    $s['status'] = 'ended';
                    $s['reason'] = substr((string)($p['reason'] ?? ''), 0, 40);
                }
                $this->clearPreview($s);
                return ['ok' => true];
            }
            if ($s['expires'] <= time() || in_array($s['status'], self::TERMINAL, true)) {
                $this->fail('Сессия завершена', 410);
            }
            if ($action === 'begin') {
                if ($s['status'] !== 'created' || empty($p['uniqueid'])) {
                    $this->fail('Вызов уже начат', 409);
                }
                [, , $devices] = $this->access($s);
                $s['status'] = 'ringing';
                $s['guestUniqueid'] = (string)$p['uniqueid'];
                return ['ok' => true, 'id' => $s['id'], 'flatId' => $s['flatId'], 'flatNumber' => $s['flatNumber'],
                        'domophoneId' => $s['domophoneId'], 'previewHash' => $s['previewHash'], 'deviceIds' => $s['deviceIds'],
                        // The internal PBX already needs these push destinations;
                        // reuse this fresh check instead of loading the flat again.
                        'devices' => $devices,
                        'callerId' => $s['title'] . ', кв. ' . $s['flatNumber'] . ' · Виртуальный домофон'];
            }
            if ($action === 'legs') {
                $legs = $p['legs'] ?? null;
                if ($s['status'] !== 'ringing' || !is_array($legs) || !$legs || count($legs) > 256) {
                    $this->fail();
                }
                // One fresh access check for the complete batch, rather than
                // reloading the same apartment/subscribers once per device.
                [, , $devices] = $this->access($s);
                $allowed = array_map(fn($d) => (int)$d['deviceId'], $devices);
                $prepared = [];
                foreach ($legs as $leg) {
                    if (!is_array($leg)) $this->fail();
                    $extension = (string)($leg['extension'] ?? '');
                    $deviceId = (int)($leg['deviceId'] ?? 0);
                    if (!preg_match('/^2[0-9]{9}$/D', $extension) || isset($prepared[$extension]) ||
                        !in_array($deviceId, $s['deviceIds'], true) || !in_array($deviceId, $allowed, true) ||
                        (isset($s['legs'][$extension]) && $s['legs'][$extension]['deviceId'] !== $deviceId)) {
                        $this->fail();
                    }
                    $prepared[$extension] = ['deviceId' => $deviceId];
                }
                foreach ($prepared as $extension => $leg) {
                    $s['legs'][$extension] = $leg;
                    $this->redis->setex('VI:MOBILE:' . $extension, self::TTL, $s['id']);
                    $this->redis->setex('VI:AUTH:' . $extension, self::REGISTRATION_GRACE, $s['previewHash']);
                }
                return ['ok' => true];
            }
            $extension = (string)($p['extension'] ?? '');
            $leg = $s['legs'][$extension] ?? null;
            if (!$leg) {
                $this->fail();
            }
            if ($action === 'push') {
                if ($s['status'] !== 'ringing') {
                    $this->fail();
                }
                return ['ok' => true, 'hash' => $s['previewHash']];
            }
            if ($action === 'bind') {
                if ($s['status'] !== 'ringing' || !str_starts_with($p['channel'] ?? '', 'PJSIP/' . $extension . '-') || empty($p['uniqueid'])) {
                    $this->fail();
                }
                $s['legs'][$extension]['channel'] = (string)$p['channel'];
                $s['legs'][$extension]['uniqueid'] = (string)$p['uniqueid'];
                return ['ok' => true];
            }
            if (($leg['channel'] ?? null) !== ($p['channel'] ?? '') || ($leg['uniqueid'] ?? null) !== ($p['uniqueid'] ?? '')) {
                $this->fail();
            }
            if ($action === 'answer') {
                if ($s['status'] !== 'ringing') {
                    $this->fail('На вызов уже ответили', 409);
                }
                $this->access($s, $leg['deviceId']);
                $s['status'] = 'answered';
                $s['winner'] = $extension;
                return ['ok' => true];
            }
            if ($action !== 'open' || $s['status'] !== 'answered' || ($s['winner'] ?? null) !== $extension) {
                $this->fail();
            }
            if ($s['doorStatus'] !== 'idle') {
                return ['ok' => true, 'status' => $s['doorStatus'], 'duplicate' => true];
            }
            [$entrance, $domophone, $devices] = $this->access($s, $leg['deviceId']);
            $s['doorStatus'] = 'sending';
            return ['domophone' => $domophone, 'output' => (int)$entrance['domophoneOutput'],
                'session' => $s, 'mobile' => $devices[0]['subscriber']['mobile'] ?? ''];
        });
        if (!isset($result['domophone'])) return $result;

        // A slow device must not hold the session lock or resurrect a cancelled
        // call. The persisted 'sending' state prevents duplicate relay commands.
        try {
            $this->openDevice($result['domophone'], $result['output']);
            $status = 'sent';
        } catch (Throwable) {
            $status = 'error';
        }
        $this->mutate($id, function (&$s) use ($status) { $s['doorStatus'] = $status; });
        $s = $result['session'];
        error_log('virtual-intercom ' . json_encode(['call' => $id, 'entrance' => $s['entranceId'],
            'flat' => $s['flatId'], 'device' => $s['legs'][$s['winner']]['deviceId'], 'doorStatus' => $status]));
        if ($status === 'sent') {
            try {
                $plog = \loadBackend('plog');
                if ($plog) {
                    $plog->addDoorOpenDataById(time(), $s['domophoneId'], $plog::EVENT_OPENED_BY_APP, $s['output'], $result['mobile']);
                    $this->houses->paranoidEvent($s['entranceId'], 'app', $result['mobile']);
                }
            } catch (Throwable) {
                error_log('virtual-intercom audit failed for ' . $id);
            }
        }
        return ['ok' => $status !== 'error', 'status' => $status];
    }

    private function clearPreview(array $session): void
    {
        $memfs = \loadBackend('memfs');
        if ($memfs) $memfs->putFile($session['previewHash'], '');
        $this->redis->del('shot_' . $session['previewHash'], 'live_' . $session['previewHash']);
    }

    private function openDevice(array $domophone, int $output): void
    {
        $urls = (array)($domophone['ext']->doorOpeningUrls ?? []);
        if (isset($urls[$output])) {
            $url = $urls[$output];
            if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw new RuntimeException('Invalid configured door URL');
            }
            $result = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 3, 'follow_location' => 0]]));
            if ($result === false) {
                throw new RuntimeException('Door request failed');
            }
            return;
        }
        $device = \loadDevice(type: 'domophone', model: $domophone['model'], url: $domophone['url'],
            password: $domophone['credentials'], firstTime: false, lazy: $domophone['model'] !== 'sputnik.json');
        $device->openLock($output);
    }
}
