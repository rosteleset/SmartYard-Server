<?php

namespace hw\ip\domophone\is\Entities\Switch;

use InvalidArgumentException;

/**
 * Represents a switch configuration in the IS intercom.
 */
final class SwitchConfig
{
    public ?int $type;
    public ?int $summator = null;

    /**
     * @var SwitchMatrix[]
     */
    public array $matrices = [];

    /**
     * @throws InvalidArgumentException If switch type is invalid.
     */
    public function __construct(?int $type)
    {
        if ($type !== null && $type < 0) {
            throw new InvalidArgumentException('Switch type must be null or a non-negative integer');
        }

        $this->type = $type;
    }

    /**
     * Creates a new entity from raw API response data.
     *
     * @param array<string, mixed> $data
     * @return SwitchConfig
     */
    public static function fromArray(array $data): SwitchConfig
    {
        if (!array_key_exists('type', $data)) {
            throw new InvalidArgumentException('Cannot create switch config entity: missing type');
        }

        if (!array_key_exists('matrices', $data)) {
            throw new InvalidArgumentException('Cannot create switch config entity: missing matrices');
        }

        $entity = new SwitchConfig($data['type']);

        if (array_key_exists('summator', $data)) {
            $summator = $data['summator'];

            if ($summator !== null && ($summator < 0 || $summator > 1)) {
                throw new InvalidArgumentException('Switch summator must be null or in range 0..1');
            }

            $entity->summator = $summator;
        }

        $entity->matrices = array_map(
            static fn(array $matrix): SwitchMatrix => SwitchMatrix::fromArray($matrix),
            $data['matrices'],
        );

        return $entity;
    }

    /**
     * Converts the entity to API payload format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'summator' => $this->summator,
            'matrices' => array_map(
                static fn(SwitchMatrix $matrix): array => $matrix->toArray(),
                $this->matrices,
            ),
        ];
    }
}
