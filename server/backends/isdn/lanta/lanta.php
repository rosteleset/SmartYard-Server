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
                    "+74752429949"
                ];
            }

            /**
             * @inheritDoc
             */
            function checkIncoming($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=checkIncoming&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function push($push)
            {
                $query = "";
                foreach ($push as $param => $value) {
                    if ($param != "action" && $param != "secret") {
                        $query = $query . "=" . urlencode($value) . "&";
                    }
                }
                if ($query) {
                    $query = substr($query, 0, -1);
                }

                $result = file_get_contents("https://isdn.lanta.me/isdn_api.php?action=push&secret=" . $this->config["backends"]["isdn"]["secret"] . "&" . $query);

                if (strtolower(trim($result)) !== "ok") {
                    $households = loadBackend("households");
//                    $households->dismissToken($push["token"]);
                }
                return $result;
            }
        }
    }
