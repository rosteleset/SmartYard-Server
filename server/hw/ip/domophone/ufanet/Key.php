<?php

namespace hw\ip\domophone\ufanet;

/**
 * Class providing methods for handling Ufanet device keys.
 */
final class Key
{
    /**
     * Builds a key string from the given flat number and key type.
     *
     * @param int $flatNumber The flat number associated with the key.
     * @param KeyType $type The key type enum instance.
     * @return string The key string in the format "flatNumber;type".
     */
    public static function buildKey(int $flatNumber, KeyType $type): string
    {
        return $flatNumber . ';' . $type->value;
    }

    /**
     * Filters a list of keys based on the given {@see KeyType}.
     *
     * @param string[] $keys The list of keys returned from the `/api/v1/rfids` method.
     * @param KeyType $type The key type to filter by.
     * @return string[] The filtered list of keys.
     */
    public static function filterByType(array $keys, KeyType $type): array
    {
        return array_filter(
            $keys,
            fn(string $value) => self::getType($value) === $type,
        );
    }

    /**
     * Returns the flat number from a value in the format "flatNumber;type".
     *
     * @param string $value The input string.
     * @return int|null The flat number, or null if parsing fails.
     */
    public static function getFlat(string $value): ?int
    {
        $parts = explode(';', $value);

        if (count($parts) !== 2) {
            return null;
        }

        return is_numeric($parts[0]) ? (int)$parts[0] : null;
    }

    /**
     * Returns the type as a KeyType enum from a value in the format "flatNumber;type".
     *
     * @param string $value The input string.
     * @return KeyType|null The key type enum, or null if parsing fails or no matching type.
     */
    public static function getType(string $value): ?KeyType
    {
        $parts = explode(';', $value);

        if (count($parts) !== 2) {
            return null;
        }

        return is_numeric($parts[1]) ? KeyType::tryFrom((int)$parts[1]) : null;
    }
}
