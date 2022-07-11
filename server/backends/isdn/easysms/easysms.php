<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * easysms variant of flash calls and sms sending
         */
        class easysms extends isdn
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
            function getConfirmNumbers()
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
                 * transport,
                 * extension
                 * dtmf
                 * image
                 * live
                 * callerId
                 * platform
                 * flatId
                 * flatNumber
                 * turn (turn:server:port)
                 * turnTransport
                 * stun (stun:server:port)
                 * title
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

                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=push&secret=" . $this->config["backends"]["isdn"]["secret"] . "&" . $query);
            }
        }
    }
