<?php

namespace hw\ip\domophone\is\entities;

/**
 * Represents an open code entity, providing structure for code and panel code (apartment).
 */
class OpenCode implements ArrayInterface
{

    /**
     * @var int Open code.
     */
    public int $code;

    /**
     * @var int Apartment number.
     */
    public int $panelCode;

    /**
     * OpenCode constructor.
     *
     * @param int $code Open code.
     * @param int $panelCode (Optional) Panel code (apartment number) to which the opening code applies.
     * If 0, then the code is common.
     */
    public function __construct(int $code, int $panelCode = 0)
    {
        $this->code = $code;
        $this->panelCode = $panelCode;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['code'], $data['panelCode'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'panelCode' => $this->panelCode,
        ];
    }
}
