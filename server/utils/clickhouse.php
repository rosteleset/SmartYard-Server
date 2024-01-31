<?php

    class clickhouse {
        private $host, $port, $username, $password, $database;
        private $sessionId;

        public function __construct($host, $port, $username, $password, $database = 'default')
        {
            $this->host = $host;
            $this->port = $port;
            $this->username = $username;
            $this->password = $password;
            $this->database = $database;
            $this->sessionId = null;
        }

        /**
         * Creates a persistent ClickHouse session with optional session timeout.
         *
         * @param int $sessionTimeout The session timeout value in seconds (default is 10 seconds).
         *
         * @throws Exception If there is an error during session creation.
         */
        public function createPersistentSession(int $sessionTimeout = 10)
        {
            $sessionId = uniqid();

            $queryParams = [
                'user' => $this->username,
                'password' => $this->password,
                'session_id' => $sessionId,
                'session_timeout' => $sessionTimeout,
                'query' => 'SELECT 1',
            ];

            $response = @file_get_contents("http://$this->host:$this->port/?" . http_build_query($queryParams));

            if ($response === false) {
                throw new Exception("Error during session creation: " . error_get_last()['message']);
            }

            $this->sessionId = $sessionId;
        }

        public function query($query, $outputFormat = '')
        {
            return $this->select($query, $outputFormat);
        }

        public function select($query, $outputFormat = 'FORMAT JSON') {
            $curl = curl_init();
            $headers = [];
            $queryParams = [];

            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: text/plain; charset=UTF-8',
                "X-ClickHouse-User: {$this->username}",
                "X-ClickHouse-Key: {$this->password}",
            ]);

            curl_setopt($curl, CURLOPT_HEADERFUNCTION,
                function($curl, $header) use (&$headers)
                {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2)
                        return $len;

                    $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                    return $len;
                }
            );

            if ($this->sessionId !== null) {
                $queryParams['session_id'] = $this->sessionId;
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, trim($query) . ' ' . $outputFormat);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_URL, "http://$this->host:$this->port/?" . http_build_query($queryParams));
            curl_setopt($curl, CURLOPT_POST, true);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_VERBOSE, false);

            try {
                $raw = curl_exec($curl);
                $data = @json_decode($raw, true)['data'];
            } catch (\Exception $e) {
                return false;
            }
            curl_close($curl);

            if (@$headers['x-clickhouse-exception-code']) {
                echo "*" . trim($raw) . "*\n";
                return false;
            }

            return $data;
        }

        public function insert($table, $data) {
            $curl = curl_init();
            $headers = [];

            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: text/plain; charset=UTF-8',
                "X-ClickHouse-User: {$this->username}",
                "X-ClickHouse-Key: {$this->password}",
            ]);

            curl_setopt($curl, CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$headers) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);

                    if (count($header) < 2) {
                        return $len;
                    }

                    $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                    return $len;
                }
            );

            $_data = "";

            foreach ($data as $line) {
                $_data .= json_encode($line) . "\n";
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_URL, "http://{$this->host}:{$this->port}/?async_insert=1&wait_for_async_insert=0&query=" . urlencode("INSERT INTO {$this->database}.$table FORMAT JSONEachRow"));
            curl_setopt($curl, CURLOPT_POST, true);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_VERBOSE, false);

            try {
                $error = curl_exec($curl);
            } catch (\Exception $e) {
                return false;
            }
            curl_close($curl);

            if (@$headers['x-clickhouse-exception-code']) {
                return $error;
            } else {
                return true;
            }
        }
    }