<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn {

        /**
         * teledome trait (common part)
         */

        trait incoming
        {
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

