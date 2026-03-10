<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn {

        /**
         * teledome trait (common part)
         */

        trait incoming {

            /**
             * @inheritDoc
             */

            function confirmNumbers() {
                return [
                    @$this->config["backends"]["isdn"]["confirm_number"] ?: "88002220374",
                ];
            }

            /**
             * @inheritDoc
             */

            function checkIncoming($id) {
                $params = [
                    'action' => 'checkIncoming',
                    'mobile' => $id,
                    'secret' => $this->config["backends"]["isdn"]["common_secret"],
                ];
                $url = "https://isdn.lanta.me/isdn_api.php?" . http_build_query($params);
                $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
                $response = (string)@file_get_contents($url, false, $context);

                return filter_var(trim($response), FILTER_VALIDATE_BOOLEAN);
            }
        }
    }
