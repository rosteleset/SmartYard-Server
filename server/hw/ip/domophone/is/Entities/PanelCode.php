<?php

namespace hw\ip\domophone\is\Entities;

use InvalidArgumentException;

/**
 * Represents a panel code in the IS intercom.
 */
final class PanelCode
{
    public int $panelCode;
    public bool $inform = false;
    public ?bool $soundOpenTh = null;
    /** @var string[] */
    public array $sipAccounts = [];
    public bool $sipCallsEnabled = true;
    public bool $handsetCallsEnabled = true;
    public ?float $answerResistance = null;
    public ?float $quiescentResistance = null;
    public ?int $thCallVolume = null;
    public ?int $thTalkVolume = null;
    public ?int $thGateVolume = null;
    public ?int $uartToVolume = null;
    public ?int $uartFromVolume = null;
    public ?int $panelTalkVolume = null;

    /**
     * @throws InvalidArgumentException If apartment number is empty or invalid.
     */
    public function __construct(int $panelCode)
    {
        if ($panelCode < 1 || $panelCode > 9999) {
            throw new InvalidArgumentException('Panel code must be in range 1..9999');
        }

        $this->panelCode = $panelCode;
    }

    /**
     * Creates a new entity from raw API response data.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): PanelCode
    {
        if (!isset($data['panelCode'])) {
            throw new InvalidArgumentException('Cannot create panel code entity: missing panelCode');
        }

        $entity = new PanelCode((int)$data['panelCode']);
        $entity->inform = $data['inform'];
        $entity->soundOpenTh = $data['soundOpenTh'];
        $entity->sipAccounts = $data['sipAccounts'];
        $entity->sipCallsEnabled = $data['callsEnabled']['sip'];
        $entity->handsetCallsEnabled = $data['callsEnabled']['handset'];
        $entity->answerResistance = $data['resistances']['answer'];
        $entity->quiescentResistance = $data['resistances']['quiescent'];
        $entity->thCallVolume = $data['volumes']['thCall'];
        $entity->thTalkVolume = $data['volumes']['thTalk'];
        $entity->thGateVolume = $data['volumes']['thGate'];
        $entity->uartToVolume = $data['volumes']['uartTo'];
        $entity->uartFromVolume = $data['volumes']['uartFrom'];
        $entity->panelTalkVolume = $data['volumes']['panelTalk'];

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
            'panelCode' => $this->panelCode,
            'inform' => $this->inform,
            'soundOpenTh' => $this->soundOpenTh,
            'sipAccounts' => $this->sipAccounts,
            'callsEnabled' => [
                'sip' => $this->sipCallsEnabled,
                'handset' => $this->handsetCallsEnabled,
            ],
            'resistances' => [
                'answer' => $this->answerResistance,
                'quiescent' => $this->quiescentResistance,
            ],
            'volumes' => [
                'thCall' => $this->thCallVolume,
                'thTalk' => $this->thTalkVolume,
                'thGate' => $this->thGateVolume,
                'uartTo' => $this->uartToVolume,
                'uartFrom' => $this->uartFromVolume,
                'panelTalk' => $this->panelTalkVolume,
            ],
        ];
    }
}
