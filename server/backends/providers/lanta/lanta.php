<?php

    /**
     * backends providers namespace
     */

    namespace backends\providers
    {

        use http\Message;

        /**
         * LanTa's variant of flash calls and sms sending
         */
        class lanta extends providers
        {

            /**
             * @inheritDoc
             */
            public function getJson()
            {
                try {
                    return json_encode(json_decode(file_get_contents($this->config["backends"]["providers"]["providers.json"])), JSON_PRETTY_PRINT);
                } catch (\Exception $e) {
                    setLastError($e->getMessage());
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function putJson($text)
            {
                try {
                    file_put_contents($this->config["backends"]["providers"]["providers.json"], json_decode(json_encode($text), true));
                } catch (\Exception $e) {
                    setLastError($e->getMessage());
                    return false;
                }

                return true;
            }
        }
    }
