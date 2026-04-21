<?php

namespace hw\ip\domophone\akuvox\Entities;

use InvalidArgumentException;

/**
 * Represents an abstract entity in the Akuvox intercom.
 */
abstract class AbstractEntity
{
    /**
     * @var array<string, string> Mapping of API keys to class properties.
     */
    protected static array $apiParamMap = [];

    /**
     * Creates an entity instance from API data.
     *
     * @param array<string, mixed> $data Raw API response.
     * @return static A new entity.
     * @throws InvalidArgumentException If the required identifier field is missing.
     */
    public static function fromArray(array $data): static
    {
        $required = static::getRequiredField();

        if (empty($data[$required])) {
            throw new InvalidArgumentException("Cannot create entity: missing required field $required");
        }

        $entity = new static($data[$required]);

        foreach (static::$apiParamMap as $apiKey => $prop) {
            if (array_key_exists($apiKey, $data)) {
                $entity->$prop = $data[$apiKey];
            }
        }

        return $entity;
    }

    /**
     * Returns the name of a required API field.
     * This field is typically used as a unique identifier.
     */
    abstract protected static function getRequiredField(): string;

    /**
     * Converts the entity into an array of API keys.
     *
     * @return array<string, mixed> Array mapping API field names to entity values.
     */
    public function toArray(): array
    {
        return array_map(fn($prop) => $this->$prop, static::$apiParamMap);
    }
}
