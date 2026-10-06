<?php

namespace hw\ip\camera\dahua;

use hw\ip\camera\entities\DetectionZone;
use InvalidArgumentException;
use UnexpectedValueException;

/** Dahua IPC HTTP API v1.40: four windows, each an 18 x 22 bitmap. */
final class MotionGrid
{
    public const ROWS = 18;
    public const COLUMNS = 22;
    public const WINDOWS = 4;
    public const FULL_ROW = (1 << self::COLUMNS) - 1;

    public static function fromZones(array $zones): array
    {
        if (count($zones) > self::WINDOWS) {
            throw new InvalidArgumentException('Dahua supports at most four motion zones');
        }
        $windows = array_fill(0, self::WINDOWS, array_fill(0, self::ROWS, 0));
        foreach (array_values($zones) as $i => $zone) {
            if (!$zone instanceof DetectionZone) {
                throw new InvalidArgumentException('Expected a DetectionZone');
            }
            foreach ([$zone->x, $zone->y, $zone->width, $zone->height] as $value) {
                if (!is_finite($value) || $value < 0 || $value > 100) {
                    throw new InvalidArgumentException('Motion coordinates must be finite percentages');
                }
            }
            if ($zone->width <= 0 || $zone->height <= 0 ||
                $zone->x + $zone->width > 100.000001 || $zone->y + $zone->height > 100.000001) {
                throw new InvalidArgumentException('Motion rectangle must fit inside the image');
            }
            // Cover every touched cell. Epsilon keeps exact grid boundaries stable.
            $left = (int)floor($zone->x * self::COLUMNS / 100 + 1e-9);
            $top = (int)floor($zone->y * self::ROWS / 100 + 1e-9);
            $right = min(self::COLUMNS, (int)ceil(($zone->x + $zone->width) * self::COLUMNS / 100 - 1e-9));
            $bottom = min(self::ROWS, (int)ceil(($zone->y + $zone->height) * self::ROWS / 100 - 1e-9));
            if ($right <= $left || $bottom <= $top) {
                throw new InvalidArgumentException('Motion rectangle is smaller than coordinate precision');
            }
            $mask = ((1 << ($right - $left)) - 1) << $left;
            for ($row = $top; $row < $bottom; $row++) {
                $windows[$i][$row] = $mask;
            }
        }
        return $windows;
    }

    /** Reject unknown layouts before writing rather than guessing a firmware's geometry. */
    public static function readWindows(array $config): array
    {
        $windows = [];
        foreach ($config as $key => $value) {
            if (!preg_match('/^MotionDetect\[0\]\.MotionDetectWindow\[(\d+)\]\.Region\[(\d+)\]$/', $key, $m)) {
                continue;
            }
            $window = (int)$m[1];
            $row = (int)$m[2];
            if ($window >= self::WINDOWS || $row >= self::ROWS ||
                !ctype_digit((string)$value) || (float)$value > self::FULL_ROW) {
                throw new UnexpectedValueException('Unsupported Dahua motion grid');
            }
            $windows[$window][$row] = (int)$value;
        }
        for ($i = 0; $i < self::WINDOWS; $i++) {
            if (count($windows[$i] ?? []) !== self::ROWS) {
                throw new UnexpectedValueException('Expected four Dahua motion windows with 18 rows each');
            }
            ksort($windows[$i]);
        }
        ksort($windows);
        return $windows;
    }

    /** Canonical grid-cell rectangles for SmartConfigurator comparison (not UI coordinates). */
    public static function canonical(array $windows): array
    {
        $rectangles = [];
        $previous = [];
        for ($y = 0; $y < self::ROWS; $y++) {
            $mask = 0;
            foreach ($windows as $rows) {
                $mask |= $rows[$y];
            }
            $current = [];
            for ($x = 0; $x < self::COLUMNS; $x++) {
                if (!(($mask >> $x) & 1)) {
                    continue;
                }
                $start = $x;
                while ($x + 1 < self::COLUMNS && (($mask >> ($x + 1)) & 1)) {
                    $x++;
                }
                $width = $x - $start + 1;
                $key = "$start:$width";
                if (isset($previous[$key])) {
                    $index = $previous[$key];
                    $rectangles[$index]->height++;
                } else {
                    $index = count($rectangles);
                    $rectangles[] = new DetectionZone($start, $y, $width, 1);
                }
                $current[$key] = $index;
            }
            $previous = $current;
        }
        return $rectangles;
    }
}
