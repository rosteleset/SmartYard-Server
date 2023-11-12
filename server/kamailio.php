<?php
    /**
     * Kamailio AUTH API
     * Generate Hash Authentication 1 (HA1) for kamailio auth module
     */
    mb_internal_encoding("UTF-8");
    require_once "utils/error.php";
    require_once "utils/loader.php";
    require_once "utils/checkint.php";
    require_once "utils/db_ext.php";
    require_once "backends/backend.php";

    header('Content-Type: application/json');

    /**
     * Get header Authorization
     * */
    function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        return $headers;
    }

    /**
     * Get access token from header
     * */
    function getBearerToken(): ?string
    {
        $headers = getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    // Check config
    try {
        $config = @json_decode(file_get_contents(__DIR__ . "/config/config.json"), true);
    } catch (Exception $e) {
        $config = false;
    }

    if (!$config) {
        try {
            $config = @json_decode(json_encode(yaml_parse_file(__DIR__ . "/config/config.yml")), true);
        } catch (Exception $e) {
            $config = false;
        }
    }

    if (!$config) {
        http_response_code(500);
        echo "config is empty\n";
        exit(1);
    }

    if (@!$config["backends"]) {
        http_response_code(500);
        echo "no backends defined\n";
        exit(1);
    }

    if (!$config["api"]["kamailio"]) {
        http_response_code(500);
        echo "no kamailio api defined\n";
        exit(1);
    }

    try {
        $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
    } catch (Exception $e) {
        echo "can't open database " . $config["db"]["dsn"] . "\n";
        echo $e->getMessage() . "\n";
        exit(1);
    }

    // Check Kamailio config
    $kamailioConfig = false;
    foreach ($config['backends']['sip']['servers'] as $server){
          if ($server['type'] === 'kamailio') {
              $kamailioConfig = $server;
              break;
          }
    }
    if (@!$kamailioConfig){
        http_response_code(500);
        echo "No Kamailio config";
        exit(1);
    }

    // check BEARER TOKEN if enable
    if (isset($kamailioConfig['auth_token'])){
        $token = getBearerToken();

        if(!$token || $token !== $kamailioConfig['auth_token']){
            http_response_code(498);
            echo json_encode(['status' => 'Invalid Token', 'message' => 'Invalid token or empty']);
            exit(1);
        }
    }

    $path = $_SERVER["REQUEST_URI"];
    $server = parse_url($config["api"]["kamailio"]);

    if ($server && $server['path']) {
        $path = substr($path, strlen($server['path']));
    }

    if ($path && $path[0] == '/') {
        $path = substr($path, 1);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $path == 'subscribers') {
        $postData = json_decode(file_get_contents("php://input"), associative:  true);

        /**
         *  TODO:
         *      -   verification endpoint and payload
         *      -   check domain
         */
        [$subscriber, $sipDomain] = explode('@', explode(':', $postData['from_uri'])[1]);

        if ($sipDomain !== $kamailioConfig['ip']){
            http_response_code(400);
            echo json_encode(['status' => 'Bad Request', 'message' => 'Invalid Sip Domain']);
            exit(1);
        }

        if (strlen((int)$subscriber) !== 10 ) {
            http_response_code(400);
            echo json_encode(['status' => 'Bad Request', 'message' => 'Invalid Username']);
            exit(1);
        }

        //Get subscriber and validate
        $flat_id = (int)substr( $subscriber, 1);
        $households = loadBackend("households");

        $flat = $households->getFlat($flat_id);

        if ($flat && $flat['sipEnabled']) {
            $sipPassword = $flat['sipPassword'];
            //md5(username:realm:password)
            $ha1 = md5($subscriber .':'. $kamailioConfig['ip'] .':'. $sipPassword );
            echo json_encode(['ha1' => $ha1]);
        } else {
            //sip disabled
            http_response_code(403);
            echo json_encode(['status' => 'Forbidden', 'message' => 'SIP Not Enabled']);
        }
        exit(1);
    }

    //TODO: make response
    http_response_code(400);
    echo json_encode(['status' => 'Bad Request', 'message' => null]);


   /**
    *  Example cURL Request for Kamailio API
    *
    * @example
    *  curl --location 'http://smart-yard.server:8876/kamailio/subscribers' \
    *  --header 'Content-Type: application/json' \
    *  --header 'Authorization: Bearer example_token_from config' \
    *  --data-raw '{
    *      "from_uri":"sip:4000000019@kamailio.smart-yard.server"
    *  }'
    */