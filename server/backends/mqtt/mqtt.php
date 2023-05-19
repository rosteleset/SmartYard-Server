<?php

    /**
     * backends mqtt namespace
     */

    namespace backends\mqtt {

        use backends\backend;

        /**
         * base mqtt class
         */

        abstract class mqtt extends backend {
            /**
             * @return mixed
             */
            public function getConfig()
            {
                $cfg = $this->config["backends"]["mqtt"];
                unset($cfg["backend"]);
                unset($cfg["agent"]);
                return $cfg;
            }

            /**
             * @param string $topic
             * @param string $payload
             * @return mixed
             */
            public function broadcast($topic, $payload)
            {
                return file_get_contents($this->config["backends"]["mqtt"]["agent"], false, stream_context_create([
                    'http' => [
                        'method'  => 'POST',
                        'header'  => [
                            'Content-Type: application/json; charset=utf-8',
                            'Accept: application/json; charset=utf-8',
                        ],
                        'content' => json_encode([
                            "topic" => $topic,
                            "payload" => $payload,
                        ]),
                    ],
                ]));
            }
        }
    }