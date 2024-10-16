<?php

namespace hw\ip\domophone\is\entities;

/**
 * Represents a CMS matrix entity, providing structure for type, matrix data, and capacity.
 */
class CmsMatrix
{

    /**
     * Default ID for the matrix structure.
     */
    private const DEFAULT_ID = 1;

    /**
     * @var int|null $type The type of the matrix. Can be null if not provided.
     */
    public ?int $type;

    /**
     * @var array|null $matrix The matrix data. Can be null if not provided.
     */
    public ?array $matrix;

    /**
     * @var int|null $capacity The capacity of the matrix. Can be null if not provided.
     */
    public ?int $capacity;

    /**
     * CmsMatrix constructor.
     *
     * @param int|null $type The type of the matrix. Optional, defaults to null.
     * @param array|null $matrix The matrix data as an array. Optional, defaults to null.
     * @param int|null $capacity The capacity of the matrix. Optional, defaults to null.
     */
    public function __construct(int $type = null, array $matrix = null, int $capacity = null)
    {
        $this->type = $type;
        $this->matrix = $matrix;
        $this->capacity = $capacity;
    }

    /**
     * Converts the CmsMatrix object to an associative array.
     *
     * If the type is null, it returns an array with an empty 'matrices' key.
     * Otherwise, it includes the 'id', 'matrix', and 'capacity' in the 'matrices' array.
     *
     * @return array The associative array representation of the CmsMatrix object.
     */
    public function toArray(): array
    {
        if ($this->type === null) {
            return [
                'type' => $this->type,
                'matrices' => [],
            ];
        }

        return [
            'type' => $this->type,
            'matrices' => [
                [
                    'id' => self::DEFAULT_ID,
                    'matrix' => $this->matrix ?? [],
                    'capacity' => $this->capacity ?? 0,
                ],
            ],
        ];
    }
}
