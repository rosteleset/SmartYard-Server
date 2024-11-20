<?php

namespace hw\ip\domophone\is\entities;

interface ArrayInterface
{
    /**
     * Creates an instance of the class from the provided array of data.
     *
     * @param array $data The data to initialize the object from.
     * The array may contain various keys depending on the implementation.
     *
     * @return self Returns an instance of the class created from the array data.
     */
    public static function fromArray(array $data): self;

    /**
     * Converts the current object to an associative array.
     *
     * @return array The object data represented as an associative array.
     * The returned array should contain the object's properties mapped to keys.
     */
    public function toArray(): array;
}
