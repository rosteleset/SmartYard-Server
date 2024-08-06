<?php

namespace backends\monitoring;
use Exception;

enum AlertNames: string
{
    case ICMP_HOST_UNREACHABLE = 'ICMPHostUnreachable';
    case SIP_CLIENT_OFFLINE = 'SipClientOffline';
    case DVR_STREAM_ERROR = 'DvrStreamErr';
}

class prometheus extends monitoring
{
    protected $servers = [];

    public function __construct($config, $db, $redis, $login = false)
    {
        try {
            parent::__construct($config, $db, $redis, $login);
            require_once __DIR__ . '/../../../utils/api_exec.php';
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

    public function deviceStatus($deviceType, $host)
    {
        // TODO: Implement deviceStatus() method.
    }

    public function devicesStatus($deviceType, $hosts)
    {
        try {
            $this->log("Run devicesStatus: " . $deviceType);
            switch ($deviceType) {
                case 'domophone':
                case 'camera':
                    return $this->processAlarms($deviceType, $hosts);
            }
        } catch (Exception $e) {
            $this->log("Method devicesStatus: " . $e->getMessage());
            return null;
        }
    }

    private function processAlarms($deviceType, $hosts)
    {
        try {
            // TODO: refactor url
            $url = '/api/v1/query?query=ALERTS{alertname=~"ICMPHostUnreachable|SipClientOffline"}';
            if ($deviceType === 'camera'){
                $url = '/api/v1/query?query=ALERTS{alertname=~"ICMPHostUnreachable|DvrStreamErr"}';
            }
            $hostStatus = [];

            foreach ($hosts as $host){
                $hostStatus[$host['hostId']] = [
                    'ip' => $host['ip'],
                    'url' => $host['url'],
                    'status' => [],
                ];

                if ($deviceType === 'camera'){
                    $hostStatus[$host['hostId']]['dvrStream'] = $host['dvrStream'];
                    $hostStatus[$host['hostId']]['streamName'] = $this->getStreamName($host['dvrStream']);
                }
            }

            // alerts from prometheus
            $alerts = $this->apiCallToRndServer($url)['data']['result'];

            foreach ($alerts as $alert){
                $instance = $alert['metric']['instance'];
                $alertName = $alert['metric']['alertname'];
                $url = $alert['metric']['url'] ?? null;
                $name = $alert['metric']['name'] ?? null;

                foreach ($hostStatus as $hostId => $host){
                    // check alert type
                    if ($alertName === AlertNames::SIP_CLIENT_OFFLINE->value) {
                        if ($url && $host['url'] === $url){
                            $hostStatus[$hostId]['status'] = [
                                'status' => 'SIP error',
                                'message' => i18n('monitoring.sipRegistrationFail'),
                            ];
                            break;
                        }
                    } elseif ($alertName === AlertNames::ICMP_HOST_UNREACHABLE->value) {
                        if ($host['ip'] === $instance){
                            $hostStatus[$hostId]['status'] = [
                                'status' => 'Offline',
                                'message' => i18n('monitoring.offline'),
                            ];
                            break;
                        }
                    } elseif ($alertName === AlertNames::DVR_STREAM_ERROR->value) {
                        if ($host['streamName'] === $name) {
                            $hostStatus[$hostId]['status'] = [
                                'status' => 'DVRerr',
                                'message' => i18n('monitoring.dvrErr'),
                            ];
                            break;
                        }
                    }
                }
            }

            // If the host does not have an ALERTs status, we assume that it is OK
            foreach ($hostStatus as $hostId => $host) {
                if (empty($host['status'])) {
                    $hostStatus[$hostId]['status'] = [
                        'status' => 'OK',
                        'message' => i18n('monitoring.online'),
                    ];
                }
            }

            return $hostStatus;
        } catch (Exception $e){
            throw $e;
        }
    }

    /**
     * Parse DVR URL to stream name, tested only flussonic
     * @param $url
     * @return string|null
     */
    private function getStreamName($url): ?string
    {
        if (preg_match('/^https:\/\/[^\/]+\/([^\/]+)(?:\/(index\.m3u8|video\.m3u8))?$/', $url, $matches)) {
            return $matches[1]; // stream name
        }
        return null;
    }

    public function configureMonitoring() {
    }

    private function checkServerAvailability($server)
    {
        $url = $server['url'] . '/-/healthy'; // simple URL for check availability service
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
                $this->log("Server {$server['url']} returned an unexpected response: " . var_export($response, true));
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->log("Exception while checking server {$server['url']}: " . $e->getMessage());
            return false;
        }
    }

    private function apiCallToRndServer($endpoint)
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
            throw new Exception("All servers is down.");
        }

        // Select availability server for request
        $randomIndex = array_rand($availableServers);
        $server = $availableServers[$randomIndex];
        $url = $server['url'] . $endpoint;

        try {
            $response = $this->apiCall($url, $server['username'], $server['password']);
            $this->log("CALL: ".  $server['url']);
            return $response;
        } catch (Exception $e) {
            $this->log("API call failed for server {$server['url']}: " . $e->getMessage());
            throw $e;
        }
    }
}

