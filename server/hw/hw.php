<?php

    abstract class hw {

        public $url;

        public function __construct(string $url) {
            $this->url = $url;
        }

        /** Проверить доступность устройства */
        abstract public function ping(): bool;

        /** Перезагрузить устройство */
        abstract public function reboot();

        /** Сбросить устройство к заводским настройкам */
        abstract public function reset();

    }
