<?php

namespace hw\ip\domophone\is\entities;

/**
 * Represents an apartment entity, providing structure for type, matrix data, and capacity.
 */
class Apartment implements ArrayInterface
{

    /**
     * @var int Apartment number.
     */
    public int $panelCode;

    /**
     * @var bool Flag to enable/disable calls to SIP.
     */
    public bool $sipEnabled = true;

    /**
     * @var bool Flag to enable/disable calls to the analog handset.
     */
    public bool $handsetEnabled = true;

    /**
     * @var bool|null Flag to enable/disable the door opening sound when opening with a key linked to the apartment.
     */
    public ?bool $soundOpenTh = null;

    /**
     * @var bool Flag indicating the debtor's apartment.
     *
     * When the offline assistant is enabled and this flag is set,
     * the resident of this apartment will receive a sound notification
     * from the intercom speaker when he applies his key and the key opens the door.
     */
    public bool $debtor = false;

    /**
     * @var int|null ???
     */
    public ?int $thGateVolume = null;

    /**
     * @var int|null Volume of the handset melody during a call to an analog handset.
     */
    public ?int $thCallVolume = null;

    /**
     * @var int|null Volume in the handset when talking on an analog handset.
     */
    public ?int $thTalkVolume = null;

    /**
     * @var int|null Volume on the intercom during a SIP talk.
     */
    public ?int $uartFromVolume = null;

    /**
     * @var int|null The gain level of the intercom microphone during a SIP talk.
     */
    public ?int $uartToVolume = null;

    /**
     * @var int|null Volume on the intercom when talking on an analog handset.
     */
    public ?int $panelTalkVolume = null;

    /**
     * @var int|null Door opening level.
     */
    public ?int $answerResistance = null;

    /**
     * @var int|null Off-hook level.
     */
    public ?int $quiescentResistance = null;

    /**
     * @var string[] Array of SIP numbers.
     */
    public array $sipAccounts = [];

    /**
     * Apartment constructor.
     *
     * @param int $panelCode Apartment number.
     */
    public function __construct(int $panelCode)
    {
        $this->panelCode = $panelCode;
    }

    public static function fromArray(array $data): self
    {
        $apartment = new self($data['panelCode']);

        $apartment->sipEnabled = $data['callsEnabled']['sip'] ?? true;
        $apartment->handsetEnabled = $data['callsEnabled']['handset'] ?? true;
        $apartment->soundOpenTh = $data['soundOpenTh'] ?? null;
        $apartment->debtor = $data['debtor'] ?? false;
        $apartment->thGateVolume = $data['volumes']['thGate'] ?? null;
        $apartment->thCallVolume = $data['volumes']['thCall'] ?? null;
        $apartment->thTalkVolume = $data['volumes']['thTalk'] ?? null;
        $apartment->uartFromVolume = $data['volumes']['uartFrom'] ?? null;
        $apartment->uartToVolume = $data['volumes']['uartTo'] ?? null;
        $apartment->panelTalkVolume = $data['volumes']['panelTalk'] ?? null;
        $apartment->answerResistance = $data['resistances']['answer'] ?? null;
        $apartment->quiescentResistance = $data['resistances']['quiescent'] ?? null;
        $apartment->sipAccounts = $data['sipAccounts'] ?? [];

        return $apartment;
    }

    public function toArray(): array
    {
        return [
            'panelCode' => $this->panelCode,
            'callsEnabled' => [
                'sip' => $this->sipEnabled,
                'handset' => $this->handsetEnabled,
            ],
            'soundOpenTh' => $this->soundOpenTh,
            'debtor' => $this->debtor,
            'volumes' => [
                'thGate' => $this->thGateVolume,
                'thCall' => $this->thCallVolume,
                'thTalk' => $this->thTalkVolume,
                'uartFrom' => $this->uartFromVolume,
                'uartTo' => $this->uartToVolume,
                'panelTalk' => $this->panelTalkVolume,
            ],
            'resistances' => [
                'answer' => $this->answerResistance,
                'quiescent' => $this->quiescentResistance,
            ],
            'sipAccounts' => $this->sipAccounts,
        ];
    }
}
