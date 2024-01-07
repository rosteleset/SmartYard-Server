<?php

    namespace Kamailio;

    class Kamailio
    {
        private mixed $backend;
        private mixed $kamailioConf;
        private string $kamailioRpcUrl;

        public function __construct()
        {
            $this->loadBackend();
            $this->loadKamailioConfiguration();
        }

        /**
         * Load backend 'households'
         * @param string $backend
         * @return void
         */
        private function loadBackend(string $backend = 'households'): void
        {
            $this->backend = loadBackend($backend);

            if (!$this->backend) {
                $this->reply(555, false, false, 'No backend loaded');
                exit(1);
            }
        }

        /**
         * Replies with a custom response using the provided parameters.
         *
         * @param int $code The HTTP status code for the response.
         * @param mixed $data Additional data for the response (default: false).
         * @param mixed $name Additional name for the response (default: false).
         * @param mixed $message Additional message for the response (default: false).
         *
         * @return void
         */
        public function reply(int $code, $data = false, $name = false, $message = false): void
        {
            response($code, $data, $name, $message);
        }

        /**
         * TODO: refactor use multiple servers
         * @return void
         */
        private function loadKamailioConfiguration(): void
        {
            global $config;
            foreach ($config['backends']['sip']['servers'] as $server) {
                if ($server['type'] === 'kamailio') {
                    $this->kamailioConf = $server;
                }
            }
        }

        public function handleRequest(): void
        {
            global $config;
            $postData = json_decode(file_get_contents('php://input'), associative: true);
            $request_method = $_SERVER['REQUEST_METHOD'];
            $path = $_SERVER["REQUEST_URI"];

            // FIXME: var name
            $authApi = parse_url($config["api"]["kamailio"]);
            // update a patch process
            if ($authApi && $authApi['path']) {
                $path = substr($path, strlen($authApi['path']));
            }

            if ($path && $path[0] == '/') {
                $path = substr($path, 1);
            }

            // FIXME, test new feature ⚡
            // Handle SIP REGISTER message
            if ($request_method === 'POST' && $path === 'subscriber/hash') {
                $this->auth();
                [$subscriber, $sipDomain] = explode('@', explode(':', $postData['from_uri'])[1]);
                $this->handleExtension($subscriber, $sipDomain);
            }

            /**
             *  remove GET handler after test methods
             *  TODO:
             *      -   ⚡ handle SIP events: INFO or REGISTER
             *      -   error handlers
             *      -   test kamailio JSONRPC API methods
             *      -   getSubscriberStatus($subscriber)
             *      -   getAllSubscriberStatus()
             *      -   removeRegistration()
             *      -   pingSubscriber()
             */
            if ($request_method === 'GET') {
                $this->makeKamailioRpcUrl();
                $path = explode('/', $path);

                // getSubscriberStatus
                if (sizeof($path) === 2 && $path[0] === 'subscriber' && (strlen((int)$path[1]) === 10)) {
                    $subscriber = $path[1];
                    $this->getSubscriberStatus($subscriber);
                }

                if ($path[0] === 'subscribers') {
                    $this->getAllSubscriberStatus();
                }
            }

            response(400);
            exit(1);
        }

        /**
         * Get Bearer token
         * @return void
         */
        private function auth(): void
        {
            $receive_token = $this->getBearerToken();
            $token = $this->kamailioConf['auth_token'];

            if (!$receive_token || $receive_token !== $token) {
                $this->reply(498, null, null, 'Invalid token or empty');
                exit(1);
            }
        }

        private function getBearerToken(): ?string
        {
            $headers = $this->getAuthorizationHeader();
            if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
            return null;
        }

        private function getAuthorizationHeader(): ?string
        {
            $headers = null;
            if (isset($_SERVER['Authorization'])) {
                $headers = trim($_SERVER['Authorization']);
            } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
            }
            return $headers;
        }
        //NOTE: :

        /**
         * Validates and handles SIP extension based on predefined patterns.
         *
         * @param int $extension SIP extension
         * @param string $sipDomain SIP domain.
         *
         * @return void
         */
        private function handleExtension(int $extension, string $sipDomain): void
        {
            $sipDomain = $this->checkSipDomain($sipDomain);// validate SIP domain

            $indoorPattern = '/^4\d{9}$/';//  indoor SIP intercom extension pattern 4000000000
            $outdoorPattern = '/^1\d{5}$/';// outdoor SIP intercom extension pattern 100000

            $credential = null;

            // validate SIP extension
            if (preg_match($indoorPattern, $extension)) {
                $credential = $this->getIntercomCredentials($extension, 'indoor');
            } elseif (preg_match($outdoorPattern, $extension)) {
                $credential = $this->getIntercomCredentials($extension, 'outdoor');
            }

            if ($credential) {
                $ha1 = $this->generateHash($extension, $sipDomain, $credential);
                $this->reply(200, ['ha1' => $ha1]);
            } else {
                $this->reply(403, false, false, 'SIP not enabled');
            }

            exit(1);
        }

        /**
         * Get intercom credentials based on the extension type.
         *
         * @param int    $extension SIP extension
         * @param string $type      Intercom type ('indoor' or 'outdoor')
         *
         * @return string|null
         */
        private function getIntercomCredentials(int $extension, string $type): ?string
        {
            $id = (int)substr($extension, 1);
            $result = $type === 'indoor' ? $this->backend->getFlat($id) : $this->backend->getDomophone($id);

            if ($result) {
                if ($type === 'indoor') {
                    if ($result['sipEnabled'] && $result['sipPassword']) {
                        return $result['sipPassword'];
                    }
                } elseif ($type === 'outdoor') {
                    if ($result['enabled'] && $result['credentials']) {
                        return $result['credentials'];
                    }
                }
            }

            return null;
        }

        private function checkSipDomain($receivedSipDomain)
        {
            define("KAMAILIO_DOMAIN", $this->kamailioConf['domain']);
            // validate 'sip domain' field extension@your-sip-domain
            if ($receivedSipDomain !== KAMAILIO_DOMAIN) {
                $this->reply(400, false, false, 'Invalid Received Sip Domain');
                exit(1);
            } else {
                return $receivedSipDomain;
            }

        }

//        /**
//         * Get indoor client intercom credentials
//         *
//         * @param int $extension
//         *
//         * @return string|null
//         */
//        public function getIndoorIntercomCredentials(int $extension): ?string
//        {
//            $flat_id = (int)substr($extension, 1);
//            $result = $this->backend->getFlat($flat_id);
//
//            if ($result && $result['sipEnabled'] && $result['sipPassword']) {
//                return $result['sipPassword'];
//            }
//
//            return null;
//        }
//
//        /**
//         * Get outdoor intercom credentials
//         * @param int $extension
//         * @return string|null
//         */
//        public function getOutdoorIntercomCredentials(int $extension): ?string
//        {
//            $homophone_id = (int)substr($extension, 1);
//            $result = $this->backend->getDomophone($homophone_id);
//
//            if ($result && $result['enabled'] && $result['credentials']) {
//                return $result['credentials'];
//            }
//
//            return null;
//        }

        /**
         * Generates an MD5 hash for the provided subscriber
         *
         * @param string $subscriber The subscriber's username.
         * @param string $domain The SIP domain.
         * @param string $password The SIP password.
         *
         * @return string The MD5 hash generated using the subscriber, domain, and password.
         */
        public function generateHash(string $subscriber, string $domain, string $password): string
        {
            return md5($subscriber . ':' . $domain . ':' . $password);//md5(username:realm:password)
        }

        private function makeKamailioRpcUrl(): void
        {
            // make kamailio JSON RPC url example: http://example-host.com:8080/RPC';
            [
                'rpc_interface' => $kamailio_address,
                'rpc_port' => $kamailio_rpc_port,
                'rpc_path' => $kamailio_rpc_path,
            ] = $this->kamailioConf;

            $this->kamailioRpcUrl = 'http://' . $kamailio_address . ':' . $kamailio_rpc_port . '/' . $kamailio_rpc_path;
        }

        /**
         * @example
         * curl --location 'http://172.28.0.2/kamailio/subscriber/4000000001' \
         * --header 'Authorization: Bearer EXAMPLE_TOKEN'
         */
        public function getSubscriberStatus($subscriber): void
        {
            /**
             *  TODO:
             *      - implement API call,  get active subscriber from kamailio sip server per AOR
             */
            $postData = [
                "jsonrpc" => "2.0",
                "method" => "ul.lookup",
                "params" => ["location", $subscriber],
                "id" => 1
            ];
            try {
                $get_subscriber_info = apiExec('POST', $this->kamailioRpcUrl, $postData, false, false);
                $this->reply(200, json_decode($get_subscriber_info));
            } catch (Exception $err) {
                $this->reply(500, false, false, [$err->getMessage()]);
            }

            exit(1);
        }

        /**
         * @example
         * curl --location 'http://172.28.0.2/kamailio/subscribers' \
         * --header 'Authorization: Bearer EXAMPLE_TOKEN'
         */
        public function getAllSubscriberStatus(): void
        {
            /**
             *  TODO:
             *      - implement API call,  get all active subscriber from kamailio sip server
             */
            $postData = array(
                "jsonrpc" => "2.0",
                "method" => "ul.dump",
                "params" => [],
                "id" => 1
            );

            $res = apiExec('POST', $this->kamailioRpcUrl, $postData, false, false);
            $res = json_decode($res, true);
            header('Content-Type: application/json');
            $this->reply(200, $res['result']);
            exit(1);
        }

        public function removeRegistration($subscriber)
        {
            /**
             *  TODO:
             *      - implement API call, drop active subscriber  registration kamailio sip server
             */
        }

        public function pingSubscriber($subscriber)
        {
            /**
             *  TODO:
             *      - implement API call, make SIP ping to selected subscriber for test
             */
        }
    }

