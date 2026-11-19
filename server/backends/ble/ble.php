<?php

namespace backends\ble;

use backends\backend;
use Exception;

/**
 * Base backend for managing BLE keys.
 */
abstract class ble extends backend
{
    /**
     * Returns the ID of the subscriber who owns the BLE key.
     *
     * @param string $key BLE key in the format returned by getKeys().
     * @return int|null Subscriber ID, or null if no unique owner is known.
     */
    public function findSubscriberIdByKey(string $key): ?int
    {
        return null;
    }

    /**
     * Returns the subscriber's BLE keys for mobile applications.
     *
     * @param int $subscriberId Subscriber ID.
     * @return list<array{key: string}> BLE keys, or an empty list if none are assigned.
     * @throws Exception If the keys cannot be read.
     */
    public function getMobileKeys(int $subscriberId): array
    {
        return $this->getKeys($subscriberId);
    }

    /**
     * Deletes the subscriber's BLE key. Missing keys are ignored.
     *
     * @param int $subscriberId Subscriber ID.
     * @param string $key BLE key to delete.
     * @return void
     * @throws Exception If the key cannot be deleted.
     */
    abstract public function deleteKey(int $subscriberId, string $key): void;

    /**
     * Generates a BLE key for the subscriber.
     *
     * @param int $subscriberId Subscriber ID.
     * @return array{key: string} The subscriber's BLE key.
     * @throws Exception If the key cannot be generated.
     */
    abstract public function generateKey(int $subscriberId): array;

    /**
     * Returns the subscriber's BLE keys.
     *
     * @param int $subscriberId Subscriber ID.
     * @return list<array{key: string}> BLE keys, or an empty list if none are assigned.
     * @throws Exception If the keys cannot be read.
     */
    abstract public function getKeys(int $subscriberId): array;
}
