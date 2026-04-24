<?php

namespace hw\ip\domophone\is\Entities;

use InvalidArgumentException;

/**
 * Represents a switch configuration in the IS intercom.
 */
final class SwitchConfig
{
    public ?int $type;
    public ?int $summator = null;

    /**
     * @var array<int, array{id: int, capacity: int, matrix: array<int, array<int, int|null>>}>
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
            static fn(array $matrix): array => self::normalizeMatrix($matrix),
            $data['matrices'],
        );

        return $entity;
    }

    /**
     * Normalizes a matrix item returned by the switch API.
     *
     * @param array<string, mixed> $matrix
     * @return array{id: int, capacity: int, matrix: array<int, array<int, int|null>>}
     */
    private static function normalizeMatrix(array $matrix): array
    {
        if (!isset($matrix['id'], $matrix['capacity'], $matrix['matrix'])) {
            throw new InvalidArgumentException('Cannot create switch config entity: invalid matrix item');
        }

        if (!is_array($matrix['matrix'])) {
            throw new InvalidArgumentException('Cannot create switch config entity: matrix must be an array');
        }

        $id = $matrix['id'];
        if ($id < 1 || $id > 4) {
            throw new InvalidArgumentException('Switch matrix id must be in range 1..4');
        }

        return [
            'id' => $id,
            'capacity' => $matrix['capacity'],
            'matrix' => array_map(
                static function (mixed $row): array {
                    if (!is_array($row)) {
                        throw new InvalidArgumentException(
                            'Cannot create switch config entity: matrix row must be an array',
                        );
                    }

                    return array_map(
                        static fn(mixed $cell): ?int => $cell,
                        $row,
                    );
                },
                $matrix['matrix'],
            ),
        ];
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
            'matrices' => $this->matrices,
        ];
    }
}
