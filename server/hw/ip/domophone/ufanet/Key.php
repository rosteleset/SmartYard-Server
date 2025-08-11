<?php

namespace hw\ip\domophone\ufanet;

/**
 * Class providing methods for handling Ufanet device keys.
 */
final class Key
{
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
     * Gets the type as a KeyType enum from a value in the format "flatNumber;type".
     *
     * @param string $value The input string.
     * @return KeyType|null The parsed key type enum, or null if parsing fails or no matching type.
     */
    public static function getType(string $value): ?KeyType
    {
        $parts = explode(';', $value);
        if (!isset($parts[1]) || !is_numeric($parts[1])) {
            return null;
        }

        return KeyType::tryFrom($parts[1]);
    }
}
