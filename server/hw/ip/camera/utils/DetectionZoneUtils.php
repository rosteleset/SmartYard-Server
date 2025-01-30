<?php

namespace hw\ip\camera\utils;

use hw\ip\camera\entities\DetectionZone;
use InvalidArgumentException;

final class DetectionZoneUtils
{

    /**
     * Convert the coordinates of a `DetectionZone` object between percentage and pixels.
     *
     * @param DetectionZone $zone The `DetectionZone` object to convert.
     * @param int $maxX Maximum value for the width (e.g., max width in pixels).
     * @param int $maxY Maximum value for the height (e.g., max height in pixels).
     * @param string $direction Conversion direction: "toPixel" for percentage to pixels,
     * or "toPercent" for pixels to percentage.
     * @return DetectionZone A new `DetectionZone` object with the converted coordinates.
     * @throws InvalidArgumentException If an invalid conversion direction is provided.
     */
    public static function convertCoordinates(
        DetectionZone $zone,
        int           $maxX,
        int           $maxY,
        string        $direction,
    ): DetectionZone
    {
        if ($direction === 'toPixel') {
            return new DetectionZone(
                x: self::percentToPixel($zone->x, $maxX),
                y: self::percentToPixel($zone->y, $maxY),
                width: self::percentToPixel($zone->width, $maxX),
                height: self::percentToPixel($zone->height, $maxY),
            );
        }

        if ($direction === 'toPercent') {
            return new DetectionZone(
                x: self::pixelToPercent($zone->x, $maxX),
                y: self::pixelToPercent($zone->y, $maxY),
                width: self::pixelToPercent($zone->width, $maxX),
                height: self::pixelToPercent($zone->height, $maxY),
            );
        }

        throw new InvalidArgumentException('Invalid conversion direction. Use "toPixel" or "toPercent".');
    }

    /**
     * Convert a percentage to pixels based on the maximum value.
     *
     * @param float $percent The percentage value (0-100).
     * @param int $max The maximum dimension (e.g., max width or height in pixels).
     * @return int The equivalent pixel value.
     */
    private static function percentToPixel(float $percent, int $max): int
    {
        return (int)round(($percent / 100) * $max);
    }

    /**
     * Convert a pixel value to a percentage based on the maximum value.
     *
     * @param int $pixel The pixel value.
     * @param int $max The maximum dimension (e.g., max width or height in pixels).
     * @return float The equivalent percentage (0-100), rounded to two decimal places.
     */
    private static function pixelToPercent(int $pixel, int $max): float
    {
        return round(($pixel / $max) * 100, 2);
    }
}
