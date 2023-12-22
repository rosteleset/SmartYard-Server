<?php
    namespace Kamailio;

    use api\accounts\password;

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

        private function getAuthorizationHeader(): ?string
        {
            $headers = null;
            if (isset($_SERVER['Authorization'])) {
                $headers = trim($_SERVER['Authorization']);
            }
            else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
            }
            return $headers;
        }

        private function getBearerToken(): ?string
        {
            $headers = $this->getAuthorizationHeader();
            if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
            return null;
        }

        /**
         * Get Bearer token
         * @return void
         */
        private function auth(): void
        {
            $receive_token = $this->getBearerToken();
            $token = $this->kamailioConf['auth_token'];

            if(!$receive_token || $receive_token !== $token){
                $this->reply(498, null, null, 'Invalid token or empty');
                exit(1);
            }
        }

        /**
         * Load backend 'households'
         * @param string $backend
         * @return void
         */
        private function loadBackend($backend = 'households'): void
        {
            $this->backend = loadBackend($backend);

            if (!$this->backend) {
                $this->reply(555, false, false, 'No backend loaded');
                exit(1);
            }
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

        private function makeKamailioRpcUrl(): void
        {
            // make kamailio JSON RPC url example: http://example-host.com:8080/RPC';
            [
                'rpc_interface' => $kamailio_address,
                'rpc_port' => $kamailio_rpc_port,
                'rpc_path' => $kamailio_rpc_path,
            ] = $this->kamailioConf;

            $this->kamailioRpcUrl = 'http://'.$kamailio_address.':'.$kamailio_rpc_port.'/'.$kamailio_rpc_path;
        }

        public function handleRequest(): void
        {
            global $config;
            $postData = json_decode(file_get_contents('php://input'), associative: true);
            $request_method = $_SERVER['REQUEST_METHOD'];
            $path = $_SERVER["REQUEST_URI"];

            // FIXME: rename var
            $authApi = parse_url($config["api"]["kamailio"]);
            // update a patch process
            if ($authApi && $authApi['path']) {
                $path = substr($path, strlen($authApi['path']));
            }

            if ($path && $path[0] == '/') {
                $path = substr($path, 1);
            }

            // Handler Kamailio sip server REGISTER request
            if ($request_method === 'POST' && $path === 'subscriber/hash') {
                $this->auth();
                [$subscriber, $sipDomain] = explode('@', explode(':', $postData['from_uri'])[1]);

                $this->getSubscriberHash($subscriber, $sipDomain);
            }

            //FIXME, test new feature ⚡
            if ($request_method === 'POST' && $path === 'subscriber/hash2'){
                $this->auth();
                [$subscriber, $sipDomain] = explode('@', explode(':', $postData['from_uri'])[1]);
                $this->checkSipExtension($subscriber, $sipDomain);
            }

            /**
             *  remove GET handler after test methods
             *  TODO:
             *      -   error handlers
             *      -   test kamailio JSONRPC API methods
             *      -   getSubscriberStatus($subscriber)
             *      -   getAllSubscriberStatus()
             *      -   removeRegistration()
             *      -   pingSubscriber()
             */
            if ($request_method === 'GET'){
                $this->makeKamailioRpcUrl();
                $path = explode('/', $path);

                // getSubscriberStatus
                if (sizeof($path) === 2 && $path[0] === 'subscriber' && (strlen((int)$path[1]) === 10)){
                    $subscriber = $path[1];
                    $this->getSubscriberStatus($subscriber);
               }

                if ($path[0] === 'subscribers'){
                    $this->getAllSubscriberStatus();
                }
            }

            response(400);
            exit(1);
        }

        /**
         * Generates a hash value for the provided subscriber and SIP domain.
         *
         * @param string $subscriber The subscriber's username.
         * @param string $sipDomain The SIP domain to validate against.
         *
         * @return void
         */
        public function getSubscriberHash(string $subscriber, string $sipDomain): void
        {
            define("KAMAILIO_DOMAIN", $this->kamailioConf['domain']);
            // validate 'sip domain' field extension@your-sip-domain
            if ($sipDomain !== KAMAILIO_DOMAIN) {
                response(400, false, false, 'Invalid Received Sip Domain');
                exit(1);
            }

            // validate subscriber extension mask
            //TODO: add regexp for check extension
            if (strlen((int)$subscriber) !== 10 ) {
                response(400, false, false, 'Invalid Received Subscriber UserName');
                exit(1);
            }

            $flat_id = (int)substr($subscriber, 1);
//            $this->loadBackend('households');
            ['sipEnabled' => $sipEnabled, 'sipPassword' => $sipPassword] = $this->backend->getFlat($flat_id);

            // validate in enable SIP service for flat
            if ($sipEnabled) {
                $ha1 = md5($subscriber .':'. KAMAILIO_DOMAIN .':'. $sipPassword);//md5(username:realm:password)
                $this->reply(200, ['ha1' => $ha1]);
            } else {
                $this->reply(403, false, false, 'SIP Not Enabled');
            }
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

        public function checkSipExtension(int $extension, $sipDomain): void
        {
            $sipDomain = $this->checkSipDomain($sipDomain);

            $indoorPattern = '/^4\d{9}$/'; //  indoor SIP intercom extension pattern 4000000000
            $outdoorPattern = '/^1\d{5}$/'; // outdoor SIP intercom extension pattern 100000

            if (preg_match($indoorPattern, $extension)){
                $credential = $this->getIndoorIntercomCredentials($extension);
                if ($credential) {
                    //  generate hash and return
                    $ha1 = $this->generateHash($extension, $sipDomain, $credential );
                    $this->reply(200, ['ha1' => $ha1]);
                } else {
                    // return err
                    $this->reply(404);
                }
                exit(1);
            } elseif ( preg_match($outdoorPattern, $extension)){
                // get sip outdoor intercom credentials
                $credential = $this->getOutdoorIntercomCredentials($extension);

                if ($credential) {
                    $ha1 = $this->generateHash($extension, $sipDomain, $credential);
                    $this->reply(200, ['ha1' => $ha1]);
                }
                else {
                    $this->reply(404);
                }

            } else {
                $this->reply(400, false, false, 'Invalid Received Subscriber UserName');
                exit(1);
            }
        }

        public function getIndoorIntercomCredentials(int $extension)
        {
            $flat_id = (int)substr($extension, 1);
            ['sipEnabled' => $sipEnabled, 'sipPassword' => $sipPassword] = $this->backend->getFlat($flat_id);

            if ($sipEnabled) {
                return $sipPassword;
            } else {
                return false;
            }
        }

        public function getOutdoorIntercomCredentials(int $extension)
        {
            // get outdoor intercom password
            $result = $this->backend->getDomophone((int)substr($extension, 1));
            if ($result){
                ['enabled' => $enabled, 'credentials' => $credentials] = $result;
                if ($enabled) {
                    return $credentials;
                } else {
                    return  false;
                }
            }
        }

        public function generateHash($subscriber, $domain, $password)
        {
            return md5($subscriber . ':' . $domain . ':' . $password);//md5(username:realm:password)
        }

        /**
         * @example
         * curl --location 'http://172.28.0.2/kamailio/subscriber/4000000001' \
         * --header 'Authorization: Bearer EXAMPLETOKEN'
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
         * --header 'Authorization: Bearer EXAMPLETOKEN'
         */
        public function getAllSubscriberStatus(): void {
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

        public function removeRegistration($subscriber) {
            /**
             *  TODO:
             *      - implement API call, drop active subscriber  registration kamailio sip server
             */
        }

        public function pingSubscriber($subscriber) {
            /**
             *  TODO:
             *      - implement API call, make SIP ping to selected subscriber for test
             */
        }

        public function reply(int $code, $data = false, $name = false, $message = false): void
        {
            response($code, $data, $name, $message);
        }

    }

