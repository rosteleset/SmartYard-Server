<?php

namespace hw\ip\domophone\basip\Traits;

/**
 * Provides the default valid structure for identifiers used in BasIP intercom devices.
 */
trait IdentifierValidTrait
{
    protected static function getIdentifierValidDefaultValue(): array
    {
        return [
            'passes' => [
                'is_permanent' => true,
                'max_passes' => null,
                'time' => [
                    'from' => null,
                    'is_permanent' => true,
                    'to' => null,
                ],
            ],
        ];
    }
}
