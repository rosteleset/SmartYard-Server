<?php

namespace hw\ip\domophone\is\Entities;

use InvalidArgumentException;

/**
 * Represents an open code in the IS intercom.
 */
final class OpenCode
{
    public int $panelCode;
    public int $code;

    /**
     * @throws InvalidArgumentException If panel code or open code is out of range.
     */
    public function __construct(int $code, int $panelCode = 0)
    {
        if ($panelCode < 0 || $panelCode > 9999) {
            throw new InvalidArgumentException('Panel code must be in range 0..9999');
        }

        if ($code < 10000 || $code > 99999) {
            throw new InvalidArgumentException('Open code must be in range 10000..99999');
        }

        $this->panelCode = $panelCode;
        $this->code = $code;
    }

    /**
     * Creates a new entity from raw API response data.
     *
     * @param array<string, mixed> $data
     * @return OpenCode
     */
    public static function fromArray(array $data): OpenCode
    {
        if (!isset($data['panelCode'], $data['code'])) {
            throw new InvalidArgumentException('Cannot create open code entity: missing required fields');
        }

        return new OpenCode((int)$data['code'], (int)$data['panelCode']);
    }

    /**
     * Converts the entity to API payload format.
     *
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'panelCode' => $this->panelCode,
            'code' => $this->code,
        ];
    }
}
