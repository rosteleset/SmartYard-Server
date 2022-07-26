<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * LanTa's variant of flash calls and sms sending
         */
        class lanta extends isdn
        {

            /**
             * @inheritDoc
             */
            function flashCall($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=flashCall&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function getCode($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=checkFlash&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function sendCode($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=sendCode&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function confirmNumbers()
            {
                return [
                    "84752429949"
                ];
            }

            /**
             * @inheritDoc
             */
            function checkIncomng($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=checkIncoming&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function push($push)
            {
                /*
                 * hash ([sip] password)
                 * server (sip server)
                 * port (sip port)
                 * transport (tcp|udp)
                 * extension
                 * dtmf
                 * image (url of first camshot)
                 * live (url of "live" jpeg stream)
                 * callerId
                 * platform (ios|android)
                 * flatId
                 * flatNumber
                 * turn (turn:server:port)
                 * turnTransport (tcp|udp)
                 * stun (stun:server:port)
                 * type
                 * token
                 * msg
                 * title
                 * badge
                 * messageId
                 * pushAction (action)
                 */

                $query = "";
                foreach ($push as $param => $value) {
                    if ($param != "action" && $param != "secret") {
                        $query = $param . "=" . htmlentities($value) . "&";
                    }
                }
                if ($query) {
                    $query = substr($query, 0, -1);
                }

                $result = file_get_contents("https://isdn.lanta.me/isdn_api.php?action=push&secret=" . $this->config["backends"]["isdn"]["secret"] . "&" . $query);

                if (strtolower(trim($result)) !== "ok") {
                    $households = loadBackend("households");
                    $households->dismissToken($push["token"]);
                }
                return $result;
            }
        }
    }
