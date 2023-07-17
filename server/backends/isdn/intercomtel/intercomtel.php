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
        /**
         * @inheritDoc
         */
        function push($push)
        {
            $idsn = $this->config['backends']['isdn'];

            Logger::channel('notification')->debug('Send notification to ' . $idsn['endpoint'], ['push' => $push]);

            $request = curl_init($idsn['endpoint'] . '/api/v1/external/notification');

            curl_setopt($request, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($request, CURLOPT_USERPWD, $idsn['secret']);
            curl_setopt($request, CURLOPT_POSTFIELDS, json_decode($push));
            curl_setopt($request, CURLOPT_POST, 1);

            $response = curl_exec($request);

            curl_close($request);

            return $response;
        }

        /**
         * @inheritDoc
         */
        function sendCode($id)
        {
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
            throw new \BadMethodCallException();
        }
    }
}
