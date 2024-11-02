<?php

namespace hw\ip\domophone\ufanet;

/**
 * Class representing a Secret Mini intercom.
 */
class secretMini extends ufanet
{

    /**
     * URL used to automatically update the intercom firmware.
     */
    protected const AUTOUPDATE_ADDRESS = 'https://dom.ufanet.ru/icupd/OPENIPC';

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    ): void
    {
        $this->dialplans = [
            self::BUTTON_FACE => [ // Real apartment that is called up when you press a button
                'sip_number' => (string)($sipNumbers[0] ?? null),
                'sip' => true,
            ],
            $apartment => [ // Empty apartment for storing only apartment number
                'sip_number' => '',
                'sip' => false,
            ],
        ];
    }

    public function configureGate(array $links = []): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0 || array_key_exists($apartment, $this->dialplans)) {
            $this->dialplans = [];
        }
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setAutoupdateEnabled(false);
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTickerText(string $text = ''): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = parent::transformDbConfig($dbConfig);

        $dbConfig['tickerText'] = '';

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['cmsEnabled'] = false;
        }

        return $dbConfig;
    }

    protected function getApartments(): array
    {
        $this->loadDialplans();

        // Ensure there are exactly two dialplans and FRSI dialplan is set
        $buttonFace = $this->dialplans[self::BUTTON_FACE] ?? null;
        if (count($this->dialplans) !== 2 || $buttonFace === null) {
            return [];
        }

        // Get the number of an empty apartment
        $emptyApartmentNumber = array_key_first(array_diff_key($this->dialplans, [self::BUTTON_FACE => null]));

        return [
            $emptyApartmentNumber => [
                'apartment' => $emptyApartmentNumber,
                'code' => 0,
                'sipNumbers' => [$buttonFace['sip_number']], // Use FRSI SIP number
                'cmsEnabled' => false,
                'cmsLevels' => [],
            ],
        ];
    }

    protected function getCmsModel(): string
    {
        return '';
    }

    protected function getTickerText(): string
    {
        return '';
    }

    /**
     * Enables or disables the autoupdate feature.
     *
     * @param bool $enabled (Optional) True to enable autoupdate, false otherwise. Defaults to true.
     * @return void
     */
    protected function setAutoupdateEnabled(bool $enabled = true): void
    {
        $this->apiCall('/cgi-bin/firmware.cgi', 'POST', [
            'action' => 'autoupdate',
            'autoupdate_enable' => $enabled ? 'true' : 'false',
            'autoupdate_address' => self::AUTOUPDATE_ADDRESS,
        ], 0, true);
    }
}
