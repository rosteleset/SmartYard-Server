<?php
    namespace Kamailio;

    class Kamailio
    {
        private  mixed $backend;
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
                response(498, null, null, 'Invalid token or empty');
                exit(1);
            }
        }

        /**
         * Load backend 'households'
         * @return void
         */
        private function loadBackend($backend = 'households'): void
        {
            $this->backend = loadBackend($backend);

            if (!$this->backend) {
                response(555, 'No backend loaded');
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

            // TODO: rename var
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
            if ($request_method==='GET'){
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
                response(400, null, null, 'Invalid Received Sip Domain');
                exit(1);
            }

            // validate subscriber extension length, make constant
            if (strlen((int)$subscriber) !== 10 ) {
                response(400, null, null, 'Invalid Received Subscriber UserName');
                exit(1);
            }

            $flat_id = (int)substr($subscriber, 1);
            ['sipEnabled' => $sipEnabled, 'sipPassword' => $sipPassword] = $this->backend->getFlat($flat_id);

            if ($sipEnabled) {
                $ha1 = md5($subscriber .':'. KAMAILIO_DOMAIN .':'. $sipPassword);//md5(username:realm:password)
                response(200, ['ha1'=>$ha1]);
            } else {
                //sip disabled
                response(403, false, false, 'SIP Not Enabled');
            }
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
                response(200, json_decode($get_subscriber_info));
            } catch (Exception $err) {
                response(500, [$err->getMessage()]);
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
            echo response(200,$res['result']);

//            response(200, $res[0]['result']);
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

    }

