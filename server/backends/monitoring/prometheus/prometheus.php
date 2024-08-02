<?php

namespace backends\monitoring;
use Exception;

enum AlertNames: string
{
    case ICMP_HOST_UNREACHABLE = 'ICMPHostUnreachable';
    case SIP_CLIENT_OFFLINE = 'SipClientOffline';
}

class prometheus extends monitoring
{
    protected $servers = [];
    /**
     * @throws Exception
     */
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

    /**
     * Check Prometheus API connection
     * @return void
     * @throws Exception
     */
    private function checkApiConnection()
    {
        try {
            $url = 'http://192.168.13.39:9090/-/healthy';
            $method = 'GET';
            $contentType = 'application/json';
            $response = apiExec($method, $url, null, $contentType,  false, 3);
//            $this->log(var_export($response, true));

            if (is_object($response)
                && property_exists($response, 'message')
                && property_exists($response, 'code')
            ) {
                throw new Exception("API call error: " . $response->message . " (code: $response->code)");
            }

            if (!isset($response) &&  $response !== 'Prometheus Server is Healthy') {
                throw new Exception("Unable to connect to Prometheus API. Please check the API URL and credentials.");
            }
        } catch (Exception $e) {
            throw $e;
        }

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
                /**
                 * TODO:
                 *   - add check camera stream status
                 */
                    return $this->processAlarms($hosts);
            }
        } catch (Exception $e) {
            $this->log("Method devicesStatus: " . $e->getMessage());
            return null;
        }
    }

    private function processAlarms($hosts)
    {
        try {
            $url = '/api/v1/query?query=ALERTS{alertname%3D~%22ICMPHostUnreachable%7CSipClientOffline%22}';
            $hostStatus = [];

            foreach ($hosts as $host){
                $hostStatus[$host['hostId']] = [
                    'ip' => $host['ip'],
                    'url' => $host['url'],
                    'status' => [],
                ];
            }

            // alerts from prometheus
            $alerts = $this->apiCallToRndServer($url)['data']['result'];

            foreach ($alerts as $alert){
                $instance = $alert['metric']['instance'];
                $url = $alert['metric']['url'] ?? null;
                $alertName = $alert['metric']['alertname'];

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
                    } elseif ($alertName === AlertNames::ICMP_HOST_UNREACHABLE->value){
                        if ($host['ip'] === $instance){
                            $hostStatus[$hostId]['status'] = [
                                'status' => 'Offline',
                                'message' => i18n('monitoring.offline'),
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

    public function configureMonitoring()
    {
        $this->log("method not implemented");
        $server = $this->getServer();
        $this->log(var_export($server, true));
        $this->log("Start check server");
        $this->checkServerAvailability($server);

        // TODO: Implement configureZbx() method.
    }

    private function getServer()
    {
        $randomIndex = array_rand($this->servers);
        return $this->servers[$randomIndex];
    }

    private function checkServerAvailability($server)
    {
        $url = $server['url'] . '/-/healthy'; // Используем URL для проверки доступности
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

            // Проверяем код ответа и текст ответа
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

