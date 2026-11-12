<?php

namespace backends\ble;

use backends\backend;
use Exception;

abstract class ble extends backend
{
    /**
     * Returns the subscriber's BLE keys.
     *
     * @param int $subscriberId
     * @return list<array{key: string}> BLE keys, or an empty list if none are assigned.
     * @throws Exception If the keys cannot be read.
     */
    abstract public function getKeys(int $subscriberId): array;
}
