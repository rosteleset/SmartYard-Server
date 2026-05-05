<?php

namespace hw\ip\domophone\is\Entities\Switch;

use InvalidArgumentException;

final class SwitchMatrix
{
    public int $id;
    public int $capacity;

    /**
     * @var array<int, array<int, int|null>>
     */
    public array $matrix;

    public function __construct(int $id, int $capacity, array $matrix)
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Switch matrix id must be a positive integer');
        }

        $this->id = $id;
        $this->capacity = $capacity;
        $this->matrix = $matrix;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['id'], $data['capacity'], $data['matrix'])) {
            throw new InvalidArgumentException('Cannot create switch matrix entity: missing required fields');
        }

        if (!is_array($data['matrix'])) {
            throw new InvalidArgumentException('Cannot create switch matrix entity: matrix must be an array');
        }

        $matrix = array_map(
            static function (mixed $row): array {
                if (!is_array($row)) {
                    throw new InvalidArgumentException('Cannot create switch matrix entity: matrix row must be an array');
                }

                return array_map(
                    static fn(mixed $cell): ?int => $cell,
                    $row,
                );
            },
            $data['matrix'],
        );

        return new self($data['id'], $data['capacity'], $matrix);
    }

    /**
     * @return array{id: int, capacity: int, matrix: array<int, array<int, int|null>>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'capacity' => $this->capacity,
            'matrix' => $this->matrix,
        ];
    }
}
