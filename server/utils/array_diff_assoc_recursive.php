<?php

/**
 * Recursively computes the difference between two associative arrays.
 *
 * This function compares the values of two associative arrays, including nested arrays,
 * and returns the differences. It supports both strict (type-sensitive) and non-strict (type-coercive) comparisons.
 *
 * @param array $array1 The first array to compare.
 * @param array $array2 The second array to compare against.
 * @param bool $strict (Optional) Whether to perform strict type comparisons (default: `true`).
 *
 * @return array The differences between `$array1` and `$array2`.
 */
function array_diff_assoc_recursive(array $array1, array $array2, bool $strict = true): array
{
    $difference = [];

    foreach ($array1 as $key => $value) {
        if (is_array($value)) {
            if (!isset($array2[$key]) || !is_array($array2[$key])) {
                $difference[$key] = $value;
            } else {
                $newDiff = array_diff_assoc_recursive($value, $array2[$key], $strict);

                if (!empty($newDiff)) {
                    $difference[$key] = $newDiff;
                }
            }
        } elseif (!array_key_exists($key, $array2) || ($strict ? $array2[$key] !== $value : $array2[$key] != $value)) {
            $difference[$key] = $value;
        }
    }

    return $difference;
}
