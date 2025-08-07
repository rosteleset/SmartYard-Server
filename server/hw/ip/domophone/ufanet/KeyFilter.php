<?php

namespace hw\ip\domophone\ufanet;

/**
 * Class responsible for filtering the list of Ufanet device keys.
 */
final class KeyFilter
{
    /**
     * Filters a list of keys based on the given {@see KeyType}.
     *
     * @param string[] $keys The list of keys returned from the `/api/v1/rfids` method.
     * @param KeyType $type The key type to filter by.
     * @return string[] The filtered list of keys.
     */
    public static function byType(array $keys, KeyType $type): array
    {
        return array_filter(
            $keys,
            fn(string $v) => self::parseType($v) === $type->value,
        );
    }

    /**
     * Parses the type from a value in the format "flatNumber;type".
     *
     * @param string $value The input string.
     * @return int|null The parsed type as an integer, or null if parsing fails.
     */
    private static function parseType(string $value): ?int
    {
        $parts = explode(';', $value);
        return isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
    }
}
