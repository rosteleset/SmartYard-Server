<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * teledome trait (common part)
         */

        trait sms
        {
            /**
             * @inheritDoc
             */
            function sendCode($id)
            {
                return trim(file_get_contents("https://isdn.lanta.me/isdn_api.php?action=sendCode&mobile=$id&secret=" . $this->config["backends"]["isdn"]["sms_secret"]));
            }
        }
    }

