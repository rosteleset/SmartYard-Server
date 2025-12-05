<?php

namespace backends\monitoring;
use Exception;

enum AlertNames: string
{
    case ICMP_HOST_UNREACHABLE = 'ICMPHostUnreachable';
    case SIP_CLIENT_OFFLINE = 'SipClientOffline';
    case DVR_STREAM_ERROR = 'DvrStreamErr';
    case HTTP_HOST_UNREACHABLE = 'HTTPHostUnreachable';
}

class prometheus extends monitoring
{
    protected mixed $servers = [];
    private const string ENDPOINT_HEALTH_CHECK = '/-/healthy';
    private const string ENDPOINT_ALERTS_QUERY = '/api/v1/query?query=';

    public function __construct($config, $db, $redis, $login = false)
    {
        try {
            parent::__construct($config, $db, $redis, $login);
            require_once __DIR__ . '/../../../utils/apiExec.php';
            $this->servers = $config['backends']['monitoring']['servers'];
        } catch (Exception $e) {
            $this->log("Err: " . $e->getMessage());
            throw $e;
        }
    }

    public function cron($part)
    {
        /**
         * Implement me
         */

    }

    public function configureMonitoring(): void
    {
        $this->log("Prometheus used dynamic configuration");
    }

    public function deviceStatus($deviceType, $host)
    {
        try {
            $this->log("Run deviceStatus for {$deviceType} host IP: " . $host['ip']);
            // skip disabled host
            if (!$host['enabled']) {
                return $this->createStatusResponse("Unknown", 'monitoring.unknown');
            }
            $query = $this->buildAlertQuery($deviceType, $host['ip']);
            $alerts = $this->apiCallToRndServer(self::ENDPOINT_ALERTS_QUERY . $query)['data']['result'];
            $hostStatuses = $this->initHostStatuses($deviceType, [$host]);
            $this->processAlerts($alerts, $hostStatuses);
            $this->setDefaultStatus($hostStatuses);
            return $hostStatuses[$host['hostId']]['status'];
        } catch (Exception $e) {
            $this->log("deviceStatus error: " . $e->getMessage());
            return null;
        }
    }

    public function devicesStatus($deviceType, $hosts)
    {
        try {
            $this->log("Run devicesStatus for {$deviceType}, host count: " . count($hosts));
            $query = $this->buildAlertQuery($deviceType);
            $alerts = $this->apiCallToRndServer(self::ENDPOINT_ALERTS_QUERY . $query)['data']['result'];
            $hostStatuses = $this->initHostStatuses($deviceType, $hosts);
            $this->processAlerts($alerts, $hostStatuses);
            $this->setDefaultStatus($hostStatuses);
            return $hostStatuses;
        }catch (Exception $e) {
            $this->log("devicesStatus error: " . $e->getMessage());
            return null;
        }
    }

    private function apiCall($url, $username = null, $password = null)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
        if ($username && $password){
            curl_setopt($curl, CURLOPT_HTTPHEADER,  [
                'Authorization: Basic ' . base64_encode("$username:$password")
            ]);
        }

        $response = curl_exec($curl);

        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }

        curl_close($curl);

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $decodedResponse;
    }

    private function log(string $text): void
    {
        $dateTime = date('Y-m-d H:i:s');
        $message = "[$dateTime] || Prometheus || " . $text;
        error_log($message);
    }

    /**
     * Parse DVR URL to stream name, tested only flussonic
     * @param $url
     * @return string|null
     */
    private function getStreamName($url): ? string
    {
        if (preg_match('/^https:\/\/[^\/]+\/([^\/]+)(?:\/(index\.m3u8|video\.m3u8))?$/', $url, $matches)) {
            return $matches[1]; // stream name
        }
        return null;
    }

    private function checkServerAvailability($server): bool
    {
        $url = $server['url'] . self::ENDPOINT_HEALTH_CHECK; // simple URL for check availability service
        $method = 'GET';
        $contentType = 'text/plain';

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: ' . $contentType,
                    'Authorization: Basic ' . base64_encode($server['username'] . ':' . $server['password'])
                ],
            ]);

            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($response === false) {
                $this->log("Curl error: " . curl_error($curl));
                return false;
            }

            if ($httpCode !== 200 || trim($response) !== 'Prometheus Server is Healthy.') {
                $this->log("checkServerAvailability. Server {$server['url']} returned an unexpected response: " . var_export($response, true));
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->log("checkServerAvailability. Exception while checking server {$server['url']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @throws Exception
     */
    private function apiCallToRndServer(string $endpoint)
    {
        $servers = $this->servers;
        $serversCount =count($servers);

        // Check servers found
        if ($serversCount === 0) {
            throw new Exception("Server list is empty.");
        }

        $availableServers = [];
        $unavailableServers = [];

        // Check availability servers
        foreach ($servers as $server) {
            if ($this->checkServerAvailability($server)) {
                $availableServers[] = $server;
            } else {
                $unavailableServers[] = $server;
            }
        }

        // Availability servers is not found
        if (count($availableServers) === 0) {
            throw new Exception("All Prometheus servers is down.");
        }

        // Select availability server for request
        $randomIndex = array_rand($availableServers);
        $server = $availableServers[$randomIndex];
        $url = $server['url'] . $endpoint;

        try {
            $this->log("API call to prometheus server: ".  $server['url']);
            return $this->apiCall($url, $server['username'], $server['password']);
        } catch (Exception $e) {
            $this->log("API call failed for server {$server['url']}: " . $e->getMessage());
            throw $e;
        }
    }

    private function createStatusResponse($status, $message): array
    {
        return [
            'status' => $status,
            'message' => i18n($message),
        ];
    }

    private function buildAlertQuery(string $deviceType, ?string $instance = null): string
    {
        $alerts = match ($deviceType) {
            'domophone' => 'ALERTS{alertname=~"ICMPHostUnreachable|SipClientOffline|HTTPHostUnreachable"',
            'camera' => 'ALERTS{alertname=~"ICMPHostUnreachable|DvrStreamErr|HTTPHostUnreachable"',
        };
        if ($instance) {
            $alerts  .= ",instance=\"$instance\"";
        }
        $alerts .= '}';
        return $alerts;
    }

    private function initHostStatuses(string $deviceType, array $hosts): array
    {
        $hostStatuses = [];
        foreach ($hosts as $host) {
            $hostStatuses[$host['hostId']] = [
                'ip' => $host['ip'],
                'enabled' => $host['enabled'],
                'url' => $host['url'],
                'status' => !$host['enabled'] ? $this->createStatusResponse("Unknown", 'monitoring.unknown') : [],
            ];
            if ($deviceType === 'camera'){
                $hostStatuses[$host['hostId']]['dvrStream'] = $host['dvrStream'];
                $hostStatuses[$host['hostId']]['streamName'] = $this->getStreamName($host['dvrStream']);
            }
        }
        return $hostStatuses;
    }

    private function processAlerts(array $alerts, array &$hostStatuses): void
    {
        foreach ($alerts as $alert) {
            $instance = $alert['metric']['instance'] ?? null;
            $alertName = $alert['metric']['alertname'] ?? null;
            $url = $alert['metric']['url'] ?? null;
            $name = $alert['metric']['name'] ?? null;

            foreach ($hostStatuses as $hostId => &$host) {
                $status = $this->determinateStatus($alertName, $instance, $url, $name, $host);
                if ($status) {
                    $host['status'] = $status;
                    break;
                }
            }
        }
    }

    private function determinateStatus(string $alertName, string $instance, ?string $url, ?string $name, array $host): ?array
    {
        return match ($alertName) {
            AlertNames::SIP_CLIENT_OFFLINE->value => ($url && $host['url'] === $url) ? $this->createStatusResponse('SIP error', 'monitoring.sipRegistrationFail') : null,
            AlertNames::ICMP_HOST_UNREACHABLE->value => ($host['ip'] === $instance) ? $this->createStatusResponse('Offline','monitoring.offline') : null,
            AlertNames::DVR_STREAM_ERROR->value => ($host['streamName'] === $name) ? $this->createStatusResponse('DVR error', 'monitoring.dvrError') : null,
            AlertNames::HTTP_HOST_UNREACHABLE->value => ($host['ip'] === $instance) ? $this->createStatusResponse('Other', 'monitoring.otherErr') : null,
            default => null,
        };
    }

    private function setDefaultStatus(array &$hostStatuses): void
    {
        foreach ($hostStatuses as &$host) {
            if (empty($host['status'])) {
                $host['status'] = $this->createStatusResponse('OK', 'monitoring.online');
            }
        }
    }
}

