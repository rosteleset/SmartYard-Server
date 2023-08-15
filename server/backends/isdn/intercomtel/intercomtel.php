<?php

/**
 * backends isdn namespace
 */

namespace backends\isdn {

    use logger\Logger;

    /**
     * Intercomtel variant of flash calls and sms sending
     */
    class intercomtel extends isdn
    {
        private Logger $logger;

        public function __construct($config, $db, $redis, $login = false)
        {
            parent::__construct($config, $db, $redis, $login);

            $this->logger = Logger::channel('isdn', 'intercomtel');
        }

        /**
         * @inheritDoc
         */
        function push($push)
        {
            return $this->request($push, '/api/v1/external/notification');
        }

        function message($push)
        {
            return $this->request($push, '/api/v1/external/message');

        }

        /**
         * @inheritDoc
         */
        function sendCode($id)
        {
            $this->logger->error('Bad method call sendCode', ['id' => $id]);

            throw new \BadMethodCallException();
        }

        /**
         * @inheritDoc
         */
        function confirmNumbers()
        {
            throw new \BadMethodCallException();
        }

        /**
         * @inheritDoc
         */
        function checkIncoming($id)
        {
            $this->logger->error('Bad method call checkIncoming', ['id' => $id]);

            throw new \BadMethodCallException();
        }

        private function request($push, $endpoint)
        {
            $idsn = $this->config['backends']['isdn'];

            $request = curl_init($idsn['endpoint'] . $endpoint);

            curl_setopt($request, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($request, CURLOPT_USERPWD, $idsn['secret']);
            curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($push, JSON_UNESCAPED_UNICODE));
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($request);

            curl_close($request);

            $this->logger->error('Send notification via Intercomtel ' . $idsn['endpoint'] . $endpoint, json_decode($response, true));

            return false;
        }
    }
}
