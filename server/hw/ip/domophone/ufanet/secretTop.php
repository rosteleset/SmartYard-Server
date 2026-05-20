<?php

namespace hw\ip\domophone\ufanet;

use hw\Interface\{
    DisplayTextInterface,
    FreePassInterface,
    GateModeInterface,
};

/**
 * Represents an Ufanet Secret Top intercom.
 */
class secretTop extends ufanet implements GateModeInterface, DisplayTextInterface, FreePassInterface
{
    public function getDisplayText(): array
    {
        return array_filter($this->apiCall('/api/v1/configuration')['display']['labels'] ?? []);
    }

    public function getDisplayTextLinesCount(): int
    {
        return 3;
    }

    public function isFreePassEnabled(): bool
    {
        $doorSettings = $this->apiCall('/api/v1/configuration')['door'];
        return $doorSettings['unlock'] !== '' || $doorSettings['unlock2'] !== '';
    }

    public function isGateModeEnabled(): bool
    {
        ['type' => $type, 'mode' => $mode] = $this->apiCall('/api/v1/configuration')['commutator'];
        return $type === 'GATE' && $mode === 1;
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->loadDialplans();

        $this->dialplans['CONS'] = [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
            'map' => 0,
        ];
    }

    public function setDisplayText(array $textLines): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'display' => [
                'labels' => [
                    $textLines[0] ?? '',
                    $textLines[1] ?? '',
                    $textLines[2] ?? '',
                ],
            ],
        ]);
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'door' => [
                'unlock' => $enabled ? self::UNLOCK_DATE : '',
                'unlock2' => $enabled ? self::UNLOCK_DATE : '',
            ],
        ]);
    }

    public function setGateModeEnabled(bool $enabled): void
    {
        // TODO: need to set some CMS as default, otherwise there will be difference after disabling gate mode
        if ($enabled === false) {
            return;
        }

        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'commutator' => [
                'type' => 'GATE',
                'mode' => 1,
            ],
        ]);
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->loadDialplans();

        $this->dialplans['SOS'] = [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
            'map' => 0,
        ];
    }
}
