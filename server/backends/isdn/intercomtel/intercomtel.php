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

            $request = curl_init($idsn['endpoint'] . '/api/v1/external/notification');

            curl_setopt($request, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($request, CURLOPT_USERPWD, $idsn['secret']);
            curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($push, JSON_UNESCAPED_UNICODE));
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($request);

            curl_close($request);

            Logger::channel('notification')->debug('Send notification via Intercomtel ' . $idsn['endpoint'] . '/api/v1/external/notification', json_decode($response, true));

            return false;
        }

        /**
         * @inheritDoc
         */
        function sendCode($id)
        {
            Logger::channel('intercomtel')->debug('Bad method call sendCode', ['id' => $id]);

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
            Logger::channel('intercomtel')->debug('Bad method call checkIncoming', ['id' => $id]);

            throw new \BadMethodCallException();
        }
    }
}
