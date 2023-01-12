<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * LanTa's variant of flash calls and sms sending
         */

        require_once __DIR__ . "/../teledome/teledome.php";

        class lanta extends isdn
        {

            use teledome;

            /**
             * @inheritDoc
             */
            function flashCall($id)
            {
                return trim(file_get_contents("https://isdn.lanta.me/isdn_api.php?action=flashCall&mobile=$id&secret=" . $this->config["backends"]["isdn"]["flash_call_secret"]));
            }

            /**
             * @inheritDoc
             */
            function getCode($id)
            {
                return trim(file_get_contents("https://isdn.lanta.me/isdn_api.php?action=checkFlash&mobile=$id&secret=" . $this->config["backends"]["isdn"]["flash_call_secret"]));
            }

            /**
             * @inheritDoc
             */
            function sendCode($id)
            {
                return trim(file_get_contents("https://isdn.lanta.me/isdn_api.php?action=sendCode&mobile=$id&secret=" . $this->config["backends"]["isdn"]["sms_secret"]));
            }

            /**
             * @inheritDoc
             */
            function confirmNumbers()
            {
                return [
                    "88002220374"
                ];
            }

            /**
             * @inheritDoc
             */
            function checkIncoming($id)
            {
                return trim(file_get_contents("https://isdn.lanta.me/isdn_api.php?action=checkIncoming&mobile=$id&secret=" . $this->config["backends"]["isdn"]["common_secret"]));
            }
        }
    }
