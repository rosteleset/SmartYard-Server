<?php

namespace hw\ip\domophone\is\Entities;

use InvalidArgumentException;

/**
 * Represents an RFID key in the IS intercom.
 */
final class Key
{
    public string $uuid;
    public int $panelCode;
    public bool $encryption = false;
    public bool $mainAccess = true;
    public bool $secondAccess = true;
    /** @var array<string, bool> */
    public array $gates = [
        '0' => true,
        '1' => true,
        '2' => true,
        '3' => true,
    ];
    public int $profileNum = 0;
    public string $secretValue = '';
    public int $securityLevel = 0;
    public bool $virtual = false;

    /**
     * @throws InvalidArgumentException If key UUID is empty or panel code is out of range.
     */
    public function __construct(string $uuid, int $panelCode = 0)
    {
        if ($uuid === '') {
            throw new InvalidArgumentException('Key UUID cannot be empty');
        }

        if ($panelCode < 0 || $panelCode > 9999) {
            throw new InvalidArgumentException('Panel code must be in range 0..9999');
        }

        $this->uuid = $uuid;
        $this->panelCode = $panelCode;
    }

    /**
     * Creates a new entity from raw API response data.
     *
     * @param array<string, mixed> $data
     * @return Key
     */
    public static function fromArray(array $data): Key
    {
        if (!isset($data['uuid'], $data['panelCode'])) {
            throw new InvalidArgumentException('Cannot create key entity: missing required fields');
        }

        $entity = new Key($data['uuid'], $data['panelCode']);
        $entity->encryption = $data['encryption'];
        $entity->mainAccess = $data['access']['main'];
        $entity->secondAccess = $data['access']['second'];
        $entity->gates = $data['access']['gates'];
        $entity->profileNum = $data['profileNum'];
        $entity->secretValue = $data['secretValue'];
        $entity->securityLevel = $data['securityLevel'];
        $entity->virtual = $data['virtual'];

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
            'uuid' => $this->uuid,
            'panelCode' => $this->panelCode,
            'encryption' => $this->encryption,
            'access' => [
                'main' => $this->mainAccess,
                'second' => $this->secondAccess,
                'gates' => (object)$this->gates,
            ],
            'profileNum' => $this->profileNum,
            // 'secretValue' => $this->secretValue,
            'securityLevel' => $this->securityLevel,
            'virtual' => $this->virtual,
        ];
    }
}
