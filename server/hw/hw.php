<?php

    namespace hw {

        abstract class hw {

            public string $url;

            public function __construct(string $url) {
                $this->url = rtrim($url, '/');
            }

            /** Check device availability */
            abstract public function ping(): bool;

            /** Reboot device */
            abstract public function reboot();

            /** Reset device to factory settings */
            abstract public function reset();
        }
    }
