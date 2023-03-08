<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * teledome trait (common part)
         */

        trait flashCall
        {
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
        }
    }

