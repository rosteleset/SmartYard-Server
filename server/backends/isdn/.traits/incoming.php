<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

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
                    "88002220374" // or can use number "+74752429949", if you are ouside of Russian Federation
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

